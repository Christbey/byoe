<?php

namespace App\Services;

use App\Mail\Payment\PaymentReceipt;
use App\Mail\Payout\PayoutNotification;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Provider;
use App\Models\ProviderStripeAccount;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Transfer;

/**
 * Service for handling Stripe Connect and payment processing.
 *
 * This service manages:
 * - Creating and onboarding Stripe Connect Express accounts for providers
 * - Creating payment intents for bookings with platform fees
 * - Processing webhook events for successful payments
 * - Handling payouts to providers via Stripe transfers
 */
class StripeService
{
    /**
     * Create a new StripeService instance.
     */
    public function __construct()
    {
        // Set Stripe API key and version
        Stripe::setApiKey(config('stripe.secret_key'));
        Stripe::setApiVersion(config('stripe.api_version'));
    }

    /**
     * Create a Stripe Connect Express account for a provider.
     *
     * Creates a new Stripe Connect Express account that allows providers to receive
     * payouts for completed services. The account must go through onboarding before
     * it can accept payments.
     *
     * @param  Provider  $provider  The provider to create an account for
     * @return ProviderStripeAccount The created Stripe account record
     *
     * @throws \Exception If account creation fails
     */
    public function createConnectAccount(Provider $provider): ProviderStripeAccount
    {
        try {
            $user = $provider->user;

            // Create Stripe Connect Express account
            $account = Account::create([
                'type' => config('stripe.connect.account_type'),
                'country' => 'US',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'metadata' => [
                    'provider_id' => $provider->id,
                    'user_id' => $user->id,
                ],
            ]);

            // Create or update the provider's Stripe account record
            $stripeAccount = $provider->stripeAccount()->updateOrCreate(
                ['provider_id' => $provider->id],
                [
                    'stripe_account_id' => $account->id,
                    'details_submitted' => $account->details_submitted ?? false,
                    'charges_enabled' => $account->charges_enabled ?? false,
                    'payouts_enabled' => $account->payouts_enabled ?? false,
                ]
            );

            Log::info('Stripe Connect account created', [
                'provider_id' => $provider->id,
                'stripe_account_id' => $account->id,
            ]);

            return $stripeAccount;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect account creation failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create Stripe Connect account: '.$e->getMessage());
        }
    }

    /**
     * Create a short-lived Account Session for Stripe's embedded components.
     *
     * The client_secret is returned to the frontend, which uses it to mount
     * the <stripe-connect-account-onboarding> embedded component. The session
     * lasts ~1 hour; the fetchClientSecret callback re-calls this method if it expires.
     *
     * @return string The account session client_secret
     *
     * @throws \Exception If session creation fails
     */
    public function createAccountSession(Provider $provider): string
    {
        try {
            $stripeAccount = ProviderStripeAccount::where('provider_id', $provider->id)->first();

            if (! $stripeAccount || ! $stripeAccount->stripe_account_id) {
                throw new \Exception('Provider does not have a Stripe Connect account');
            }

            $session = \Stripe\AccountSession::create([
                'account' => $stripeAccount->stripe_account_id,
                'components' => [
                    'account_onboarding' => ['enabled' => true],
                    'account_management' => ['enabled' => true],
                ],
            ]);

            Log::info('Stripe Account Session created', [
                'provider_id' => $provider->id,
                'stripe_account_id' => $stripeAccount->stripe_account_id,
            ]);

            return $session->client_secret;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to create Stripe Account Session', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create Stripe session: '.$e->getMessage());
        }
    }

    /**
     * Generate an Express Dashboard login link for a fully-onboarded provider.
     *
     * This link takes the provider to their Stripe Express Dashboard where they can
     * view their balance, payout history, and 1099 tax documents. The link is
     * single-use and expires after a short time.
     *
     * @return string The Express Dashboard URL
     *
     * @throws \Exception If link creation fails or account is not onboarded
     */
    public function createExpressDashboardLink(Provider $provider): string
    {
        try {
            $stripeAccount = ProviderStripeAccount::where('provider_id', $provider->id)->first();

            if (! $stripeAccount || ! $stripeAccount->stripe_account_id) {
                throw new \Exception('Provider does not have a Stripe Connect account');
            }

            if (! $stripeAccount->isFullyOnboarded()) {
                throw new \Exception('Provider has not completed Stripe onboarding');
            }

            $loginLink = Account::createLoginLink($stripeAccount->stripe_account_id);

            Log::info('Express Dashboard login link created', [
                'provider_id' => $provider->id,
                'stripe_account_id' => $stripeAccount->stripe_account_id,
            ]);

            return $loginLink->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to create Express Dashboard login link', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create dashboard link: '.$e->getMessage());
        }
    }

    /**
     * Create an account onboarding link for a provider.
     *
     * Generates a URL that allows providers to complete their Stripe Connect
     * onboarding process. The provider must complete this process before they
     * can receive payouts.
     *
     * @param  Provider  $provider  The provider to create an onboarding link for
     * @param  string  $returnUrl  URL to redirect to after successful onboarding
     * @param  string  $refreshUrl  URL to redirect to if the link expires
     * @return string The onboarding URL
     *
     * @throws \Exception If link creation fails
     */
    public function createAccountOnboardingLink(Provider $provider, string $returnUrl, string $refreshUrl): string
    {
        try {
            // Use a fresh DB query to avoid stale Eloquent relationship cache
            // (the cache can be null if the account was just created in the same request)
            $stripeAccount = ProviderStripeAccount::where('provider_id', $provider->id)->first();

            if (! $stripeAccount || ! $stripeAccount->stripe_account_id) {
                throw new \Exception('Provider does not have a Stripe Connect account');
            }

            $accountLink = AccountLink::create([
                'account' => $stripeAccount->stripe_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            Log::info('Stripe onboarding link created', [
                'provider_id' => $provider->id,
                'stripe_account_id' => $stripeAccount->stripe_account_id,
            ]);

            return $accountLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Stripe onboarding link creation failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create onboarding link: '.$e->getMessage());
        }
    }

    /**
     * Create or retrieve a Stripe Customer for a shop.
     *
     * Idempotent: if the shop already has a stripe_customer_id it is returned directly
     * without hitting the Stripe API again.
     *
     * @throws \Exception
     */
    public function createOrRetrieveShopCustomer(Shop $shop): string
    {
        if ($shop->stripe_customer_id) {
            return $shop->stripe_customer_id;
        }

        try {
            $customer = \Stripe\Customer::create([
                'email' => $shop->user->email,
                'name' => $shop->name,
                'metadata' => [
                    'shop_id' => $shop->id,
                    'user_id' => $shop->user_id,
                ],
            ]);

            $shop->update(['stripe_customer_id' => $customer->id]);

            Log::info('Stripe Customer created for shop', [
                'shop_id' => $shop->id,
                'stripe_customer_id' => $customer->id,
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Customer for shop', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create Stripe Customer: '.$e->getMessage());
        }
    }

    /**
     * Create a Stripe SetupIntent for saving a shop's payment method.
     *
     * Returns the client_secret which the frontend uses to mount the Stripe
     * Payment Element in "setup" mode (no charge, just card saving).
     *
     * @throws \Exception
     */
    public function createShopSetupIntent(Shop $shop): string
    {
        try {
            $customerId = $this->createOrRetrieveShopCustomer($shop);

            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
                'metadata' => [
                    'shop_id' => $shop->id,
                ],
            ]);

            Log::info('Stripe SetupIntent created for shop', [
                'shop_id' => $shop->id,
                'setup_intent_id' => $setupIntent->id,
            ]);

            return $setupIntent->client_secret;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe SetupIntent for shop', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create SetupIntent: '.$e->getMessage());
        }
    }

    /**
     * Attach a confirmed payment method to the shop's Stripe Customer and set it as default.
     *
     * Called after stripe.confirmSetup() resolves on the frontend. Attaches the payment
     * method to the customer, sets it as the customer's default, and persists the
     * stripe_payment_method_id on the Shop model.
     *
     * @throws \Exception
     */
    public function saveShopPaymentMethod(Shop $shop, string $paymentMethodId): void
    {
        try {
            $customerId = $this->createOrRetrieveShopCustomer($shop);

            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            \Stripe\Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            $shop->update(['stripe_payment_method_id' => $paymentMethodId]);

            Log::info('Payment method saved for shop', [
                'shop_id' => $shop->id,
                'payment_method_id' => $paymentMethodId,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Failed to save payment method for shop', [
                'shop_id' => $shop->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to save payment method: '.$e->getMessage());
        }
    }

    /**
     * Create a payment authorization (hold) on the shop's card when they post a service request.
     *
     * Uses capture_method: manual so funds are held but not charged until a provider accepts.
     * The authorization must be captured within 7 days (Stripe limit).
     *
     * @param  ServiceRequest  $serviceRequest
     *
     * @throws \Exception
     */
    public function createServiceRequestAuthorization(\App\Models\ServiceRequest $serviceRequest): void
    {
        try {
            $amountInCents = (int) round($serviceRequest->price * 100);

            // Load the shop to check for a saved payment method
            $serviceRequest->loadMissing('shopLocation.shop');
            $shop = $serviceRequest->shopLocation->shop;
            $hasSavedCard = $shop->stripe_customer_id && $shop->stripe_payment_method_id;

            $params = [
                'amount' => $amountInCents,
                'currency' => config('stripe.payment.currency', 'usd'),
                'capture_method' => 'manual',
                'metadata' => [
                    'service_request_id' => $serviceRequest->id,
                ],
            ];

            // If the shop has a saved card, confirm the intent off-session immediately
            // so they don't need to re-enter card details on the ShowServiceRequest page.
            if ($hasSavedCard) {
                $params['customer'] = $shop->stripe_customer_id;
                $params['payment_method'] = $shop->stripe_payment_method_id;
                $params['confirm'] = true;
                $params['off_session'] = true;
            }

            $paymentIntent = PaymentIntent::create($params);

            $serviceRequest->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_client_secret' => $paymentIntent->client_secret,
            ]);

            Log::info('Service request payment authorization created', [
                'service_request_id' => $serviceRequest->id,
                'payment_intent_id' => $paymentIntent->id,
                'used_saved_card' => $hasSavedCard,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Failed to create service request authorization', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create payment authorization: '.$e->getMessage());
        }
    }

    /**
     * Capture the authorized payment when a provider accepts a service request.
     *
     * Retrieves the previously authorized PaymentIntent and captures it,
     * moving the held funds into the platform account. A payout to the
     * provider is handled separately via processPayout().
     *
     *
     * @throws \Exception
     */
    public function captureServiceRequestPayment(\App\Models\ServiceRequest $serviceRequest, Booking $booking): Payment
    {
        try {
            if (! $serviceRequest->stripe_payment_intent_id) {
                throw new \Exception('Service request has no payment authorization on file.');
            }

            $paymentIntent = PaymentIntent::retrieve($serviceRequest->stripe_payment_intent_id);

            if ($paymentIntent->status !== 'requires_capture') {
                throw new \Exception('Payment authorization has expired or is not in a capturable state.');
            }

            $captured = $paymentIntent->capture();

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'stripe_payment_intent_id' => $captured->id,
                'stripe_client_secret' => $serviceRequest->stripe_client_secret,
                'amount' => $booking->service_price,
                'currency' => config('stripe.payment.currency', 'usd'),
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);

            Log::info('Service request payment captured', [
                'service_request_id' => $serviceRequest->id,
                'booking_id' => $booking->id,
                'payment_intent_id' => $captured->id,
            ]);

            // Email receipt to shop owner
            $shopUser = $booking->serviceRequest->shopLocation->shop->user;
            Mail::to($shopUser)->queue(new PaymentReceipt($payment));

            return $payment;
        } catch (ApiErrorException $e) {
            Log::error('Failed to capture service request payment', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to capture payment: '.$e->getMessage());
        }
    }

    /**
     * Create a payment intent for a booking.
     *
     * Creates a Stripe PaymentIntent with an application fee (platform fee).
     * The payment is captured automatically and the fee is retained by the platform,
     * with the remainder going to the provider's Connect account.
     *
     * @param  Booking  $booking  The booking to create a payment for
     * @return Payment The created payment record
     *
     * @throws \Exception If payment intent creation fails
     */
    public function createPaymentIntent(Booking $booking): Payment
    {
        try {
            $provider = $booking->provider;
            $stripeAccount = $provider->stripeAccount;

            if (! $stripeAccount || ! $stripeAccount->stripe_account_id) {
                throw new \Exception('Provider does not have a Stripe Connect account');
            }

            if (! $stripeAccount->isFullyOnboarded()) {
                throw new \Exception('Provider has not completed Stripe onboarding');
            }

            // Convert amount to cents (Stripe uses smallest currency unit)
            $totalAmount = (int) round($booking->totalAmount() * 100);
            $platformFee = (int) round($booking->platform_fee * 100);

            // Create payment intent with application fee
            $paymentIntent = PaymentIntent::create([
                'amount' => $totalAmount,
                'currency' => config('stripe.payment.currency', 'usd'),
                'application_fee_amount' => $platformFee,
                'transfer_data' => [
                    'destination' => $stripeAccount->stripe_account_id,
                ],
                'metadata' => [
                    'booking_id' => $booking->id,
                    'provider_id' => $provider->id,
                ],
                'capture_method' => config('stripe.capture_method'),
                'payment_method_types' => config('stripe.payment_methods'),
            ]);

            // Create payment record (store client_secret so the shop can load the payment form on return visits)
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_client_secret' => $paymentIntent->client_secret,
                'amount' => $booking->totalAmount(),
                'currency' => config('stripe.payment.currency', 'usd'),
                'status' => 'pending',
            ]);

            Log::info('Payment intent created', [
                'booking_id' => $booking->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $totalAmount,
            ]);

            return $payment;
        } catch (ApiErrorException $e) {
            Log::error('Payment intent creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to create payment intent: '.$e->getMessage());
        }
    }

    /**
     * Handle a successful payment intent webhook event.
     *
     * Updates the payment record when a payment succeeds and schedules a payout
     * to the provider based on the configured hold period.
     *
     * @param  mixed  $paymentIntent  The Stripe PaymentIntent object from the webhook
     *
     * @throws \Exception If processing fails
     */
    /**
     * Check whether a service request's PaymentIntent is in requires_capture state.
     *
     * Used after authorization to determine if the request can be opened immediately
     * (saved card, off-session confirm) or needs the shop to complete payment on-site.
     */
    /**
     * Sync a provider's Stripe account status from the Stripe API into the database.
     *
     * Called after the embedded onboarding component's onExit fires so the database
     * reflects the latest state before the page reloads — without waiting for the
     * account.updated webhook to arrive.
     *
     * @return array{charges_enabled: bool, payouts_enabled: bool, details_submitted: bool}
     */
    public function syncStripeAccount(ProviderStripeAccount $stripeAccount): array
    {
        $account = Account::retrieve($stripeAccount->stripe_account_id);

        $updates = [
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
        ];

        if (
            ($account->charges_enabled ?? false) &&
            ($account->payouts_enabled ?? false) &&
            ($account->details_submitted ?? false) &&
            ! $stripeAccount->onboarding_completed_at
        ) {
            $updates['onboarding_completed_at'] = now();
        }

        $stripeAccount->update($updates);

        return [
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
        ];
    }

    public function paymentIntentRequiresCapture(\App\Models\ServiceRequest $serviceRequest): bool
    {
        if (! $serviceRequest->stripe_payment_intent_id) {
            return false;
        }

        try {
            $pi = PaymentIntent::retrieve($serviceRequest->stripe_payment_intent_id);

            return $pi->status === 'requires_capture';
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve PaymentIntent status', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel a PaymentIntent if it is in a cancellable state.
     *
     * Silently logs a warning if cancellation fails so the caller can continue
     * with request cancellation regardless.
     */
    public function cancelPaymentIntentIfCancellable(string $paymentIntentId): void
    {
        $cancellableStatuses = ['requires_payment_method', 'requires_confirmation', 'requires_capture'];

        try {
            $pi = PaymentIntent::retrieve($paymentIntentId);

            if (in_array($pi->status, $cancellableStatuses)) {
                PaymentIntent::cancel($paymentIntentId);
            }
        } catch (ApiErrorException $e) {
            Log::warning('Failed to cancel Stripe PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handlePaymentIntentSucceeded($paymentIntent): void
    {
        try {
            DB::beginTransaction();

            // Find the payment by Stripe payment intent ID
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if (! $payment) {
                Log::warning('Payment not found for webhook', [
                    'payment_intent_id' => $paymentIntent->id,
                ]);
                DB::rollBack();

                return;
            }

            // Update payment — only overwrite paid_at if not already captured
            $updates = ['payment_method_type' => $paymentIntent->payment_method_types[0] ?? null];
            $wasAlreadySucceeded = $payment->isSucceeded();
            if (! $wasAlreadySucceeded) {
                $updates['status'] = 'succeeded';
                $updates['paid_at'] = now();
            }
            $payment->update($updates);

            $booking = $payment->booking;

            // Confirm the booking if it's still pending
            if ($booking->isPending()) {
                $booking->confirm();
            }

            // Schedule payout only if one doesn't already exist for this booking
            $payout = null;
            if (! $booking->payout) {
                $holdDays = config('marketplace.payout.auto_payout_delay_days', 3);
                $scheduledFor = now()->addDays($holdDays);

                $payout = Payout::create([
                    'booking_id' => $booking->id,
                    'provider_id' => $booking->provider_id,
                    'amount' => $booking->provider_payout,
                    'currency' => config('stripe.payment.currency', 'usd'),
                    'status' => 'scheduled',
                    'scheduled_for' => $scheduledFor,
                ]);
            }

            DB::commit();

            // Send receipt to shop owner if payment wasn't already captured via sync flow
            if (! $wasAlreadySucceeded) {
                $shopUser = $booking->serviceRequest->shopLocation->shop->user;
                Mail::to($shopUser)->queue(new PaymentReceipt($payment));
            }

            // Notify provider of scheduled payout
            if ($payout) {
                Mail::to($booking->provider->user)->queue(new PayoutNotification($payout));
            }

            Log::info('Payment succeeded and payout scheduled', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'scheduled_for' => $payout?->scheduled_for,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error handling payment intent succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process a payout to a provider's Stripe Connect account.
     *
     * Creates a Stripe Transfer to send funds from the platform account to the
     * provider's Connect account. This is called after the hold period expires
     * for a completed booking.
     *
     * @param  Payout  $payout  The payout to process
     *
     * @throws \Exception If payout processing fails
     */
    /**
     * Issue a Stripe refund against a succeeded payment.
     *
     * @throws \Exception If the Stripe API call fails
     */
    public function refundPayment(Payment $payment, float $amount): void
    {
        try {
            $refund = Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => (int) round($amount * 100),
            ]);

            $payment->markAsRefunded();

            Log::info('Stripe refund created', [
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
                'amount' => $amount,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to process refund: '.$e->getMessage());
        }
    }

    public function processPayout(Payout $payout): void
    {
        try {
            DB::beginTransaction();

            $provider = $payout->provider;
            $stripeAccount = $provider->stripeAccount;

            if (! $stripeAccount || ! $stripeAccount->stripe_account_id) {
                throw new \Exception('Provider does not have a Stripe Connect account');
            }

            // Update payout status to processing
            $payout->markAsProcessing();

            // Convert amount to cents
            $amount = (int) round($payout->amount * 100);

            // Create transfer to provider's account
            $transfer = Transfer::create([
                'amount' => $amount,
                'currency' => $payout->currency,
                'destination' => $stripeAccount->stripe_account_id,
                'metadata' => [
                    'payout_id' => $payout->id,
                    'booking_id' => $payout->booking_id,
                    'provider_id' => $provider->id,
                ],
            ]);

            // Update payout with transfer ID and mark as paid
            $payout->markAsPaid($transfer->id);

            DB::commit();

            Log::info('Payout processed successfully', [
                'payout_id' => $payout->id,
                'transfer_id' => $transfer->id,
                'amount' => $amount,
            ]);
        } catch (ApiErrorException $e) {
            DB::rollBack();
            $payout->markAsFailed($e->getMessage());

            Log::error('Payout processing failed', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to process payout: '.$e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payout error', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
