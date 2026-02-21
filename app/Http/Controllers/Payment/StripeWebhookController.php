<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Handle incoming Stripe webhook events.
     *
     * Verifies the webhook signature and processes payment-related events:
     * - payment_intent.succeeded: Updates payment and schedules payout
     * - payment_intent.payment_failed: Handles failed payments
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::error('Stripe webhook secret not configured');

            return response()->json([
                'error' => 'Webhook secret not configured',
            ], 500);
        }

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook invalid payload', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid payload',
            ], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 400);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->stripeService->handlePaymentIntentSucceeded($paymentIntent);

                    Log::info('Stripe webhook: payment_intent.succeeded processed', [
                        'payment_intent_id' => $paymentIntent->id,
                    ]);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);

                    Log::info('Stripe webhook: payment_intent.payment_failed processed', [
                        'payment_intent_id' => $paymentIntent->id,
                    ]);
                    break;

                case 'account.updated':
                    $account = $event->data->object;
                    $this->handleAccountUpdated($account);

                    Log::info('Stripe webhook: account.updated processed', [
                        'account_id' => $account->id,
                    ]);
                    break;

                default:
                    Log::info('Stripe webhook: unhandled event type', [
                        'event_type' => $event->type,
                    ]);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            // Return 200 to prevent Stripe from retrying
            return response()->json([
                'status' => 'error',
                'message' => 'Event received but processing failed',
            ], 200);
        }
    }

    /**
     * Handle payment intent failed event.
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        // Find payment by Stripe payment intent ID
        $payment = \App\Models\Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            Log::warning('Payment not found for failed payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        // Update payment status
        $payment->update([
            'status' => 'failed',
        ]);

        // Cancel the booking
        $booking = $payment->booking;
        if ($booking && ! $booking->isCancelled()) {
            $booking->cancel('Payment failed');
        }

        Log::info('Payment failed and booking cancelled', [
            'payment_id' => $payment->id,
            'booking_id' => $booking?->id,
        ]);
    }

    /**
     * Handle Stripe Connect account updated event.
     */
    protected function handleAccountUpdated($account): void
    {
        // Find provider stripe account
        $stripeAccount = \App\Models\ProviderStripeAccount::where('stripe_account_id', $account->id)->first();

        if (! $stripeAccount) {
            Log::warning('Stripe account not found', [
                'account_id' => $account->id,
            ]);

            return;
        }

        $updates = [
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
        ];

        // Stamp completion time the first time all capabilities are granted
        if (
            ($account->charges_enabled ?? false) &&
            ($account->payouts_enabled ?? false) &&
            ($account->details_submitted ?? false) &&
            ! $stripeAccount->onboarding_completed_at
        ) {
            $updates['onboarding_completed_at'] = now();
        }

        $stripeAccount->update($updates);

        Log::info('Stripe Connect account updated', [
            'provider_stripe_account_id' => $stripeAccount->id,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
        ]);
    }
}
