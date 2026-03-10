<?php

namespace App\Services;

use App\Mail\Booking\BookingConfirmation;
use App\Mail\Payout\PayoutNotification;
use App\Mail\Rating\RatingReminder;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Provider;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service for managing bookings and business logic.
 *
 * This service handles:
 * - Creating bookings from service requests
 * - Calculating platform fees and provider payouts
 * - Completing bookings and updating provider statistics
 * - Cancelling bookings with refund logic
 */
class BookingService
{
    /**
     * Create a new BookingService instance.
     *
     * @param  StripeService  $stripeService  Service for handling Stripe operations
     */
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    /**
     * Accept a service request and create a booking.
     *
     * Creates a booking when a provider accepts a service request. Calculates
     * the platform fee and provider payout based on the service price, then
     * marks the service request as filled.
     *
     * @param  ServiceRequest  $request  The service request to accept
     * @param  Provider  $provider  The provider accepting the request
     * @return Booking The created booking
     *
     * @throws \Exception If booking creation fails
     */
    public function acceptServiceRequest(ServiceRequest $request, Provider $provider): Booking
    {
        try {
            DB::beginTransaction();

            // Lock the row to prevent two providers from accepting simultaneously
            $lockedRequest = ServiceRequest::lockForUpdate()->find($request->id);

            if (! $lockedRequest || ! $lockedRequest->isOpen() || $lockedRequest->isExpired()) {
                throw new \Exception('Service request is no longer available');
            }

            // Calculate fees
            $fees = $this->calculateFees((float) $lockedRequest->price);

            // Create the booking
            $booking = Booking::create([
                'service_request_id' => $lockedRequest->id,
                'provider_id' => $provider->id,
                'service_price' => $fees['service_price'],
                'platform_fee' => $fees['platform_fee'],
                'provider_payout' => $fees['provider_payout'],
                'status' => 'pending',
                'accepted_at' => now(),
            ]);

            // Capture the shop's pre-authorized payment if one exists.
            // Requests created before the payment-authorization flow have no intent on file;
            // they skip capture and are confirmed directly.
            if ($lockedRequest->stripe_payment_intent_id) {
                $this->stripeService->captureServiceRequestPayment($lockedRequest, $booking);

                // Schedule payout to provider inside the transaction so the webhook
                // race condition (payment_intent.succeeded firing before commit) can
                // never result in a missing Payout record.
                $holdDays = config('marketplace.payout.auto_payout_delay_days', 3);
                $payout = Payout::create([
                    'booking_id' => $booking->id,
                    'provider_id' => $provider->id,
                    'amount' => $fees['provider_payout'],
                    'currency' => config('stripe.payment.currency', 'usd'),
                    'status' => 'scheduled',
                    'scheduled_for' => now()->addDays($holdDays),
                ]);
            }

            $booking->update(['status' => 'confirmed']);

            // Mark service request as filled
            $lockedRequest->markAsFilled();

            DB::commit();

            Log::info('Service request accepted', [
                'booking_id' => $booking->id,
                'service_request_id' => $request->id,
                'provider_id' => $provider->id,
            ]);

            // Notify provider and shop owner of the new booking
            $booking->load(['serviceRequest.shopLocation.shop.user', 'provider.user']);
            $providerUser = $booking->provider->user;
            $shopUser = $booking->serviceRequest->shopLocation->shop->user;
            Mail::to($providerUser)->queue(new BookingConfirmation($booking, $providerUser));
            Mail::to($shopUser)->queue(new BookingConfirmation($booking, $shopUser));

            if (isset($payout)) {
                Mail::to($providerUser)->queue(new PayoutNotification($payout));
            }

            return $booking;
        } catch (UniqueConstraintViolationException) {
            DB::rollBack();
            Log::warning('Race condition on service request accept', [
                'service_request_id' => $request->id,
                'provider_id' => $provider->id,
            ]);
            throw new \Exception('This service request was just accepted by another provider.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept service request', [
                'service_request_id' => $request->id,
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate platform fee and provider payout from service price.
     *
     * Calculates the breakdown of a service price:
     * - Service price: The amount charged to the customer
     * - Platform fee: Percentage taken by the platform
     * - Provider payout: Amount paid to the provider after fees
     *
     * @param  float  $servicePrice  The total service price
     * @return array{service_price: float, platform_fee: float, provider_payout: float}
     */
    public function calculateFees(float $servicePrice): array
    {
        $platformFeePercentage = config('marketplace.platform_fee_percentage');
        $platformFee = round($servicePrice * ($platformFeePercentage / 100), 2);
        $providerPayout = round($servicePrice - $platformFee, 2);

        return [
            'service_price' => $servicePrice,
            'platform_fee' => $platformFee,
            'provider_payout' => $providerPayout,
        ];
    }

    /**
     * Complete a booking after service has been rendered.
     *
     * Updates the booking status to completed and increments the provider's
     * completed bookings count. This triggers the payout process if payment
     * has already been received.
     *
     * @param  Booking  $booking  The booking to complete
     *
     * @throws \Exception If completion fails
     */
    public function completeBooking(Booking $booking): void
    {
        try {
            DB::beginTransaction();

            // Validate booking can be completed
            if ($booking->isCompleted()) {
                throw new \Exception('Booking is already completed');
            }

            if ($booking->isCancelled()) {
                throw new \Exception('Cannot complete a cancelled booking');
            }

            $booking->complete();
            $provider = $booking->provider;

            DB::commit();

            Log::info('Booking completed', [
                'booking_id' => $booking->id,
                'provider_id' => $provider->id,
            ]);

            // Remind both parties to leave a rating
            $booking->load(['serviceRequest.shopLocation.shop.user', 'provider.user']);
            $shopUser = $booking->serviceRequest->shopLocation->shop->user;
            Mail::to($provider->user)->queue(new RatingReminder($booking, $provider->user));
            Mail::to($shopUser)->queue(new RatingReminder($booking, $shopUser));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a booking and handle refunds based on cancellation policy.
     *
     * Cancels a booking and determines refund eligibility based on how far in
     * advance the cancellation occurs:
     * - 48+ hours before: Full refund
     * - 24-48 hours before: Partial refund (50%)
     * - Less than 24 hours: No refund
     *
     * @param  Booking  $booking  The booking to cancel
     * @param  string  $reason  The reason for cancellation
     * @return bool True if cancellation was successful
     *
     * @throws \Exception If cancellation fails
     */
    public function cancelBooking(Booking $booking, string $reason): bool
    {
        try {
            DB::beginTransaction();

            // Validate booking can be cancelled
            if ($booking->isCancelled()) {
                throw new \Exception('Booking is already cancelled');
            }

            if ($booking->isCompleted()) {
                throw new \Exception('Cannot cancel a completed booking');
            }

            $serviceRequest = $booking->serviceRequest;
            $serviceDate = $serviceRequest->service_date
                ? Carbon::parse($serviceRequest->service_date)
                : Carbon::parse($serviceRequest->getRawOriginal('service_date'));

            if ($serviceRequest->start_time) {
                $serviceStartTime = Carbon::parse((string) $serviceRequest->start_time);
                $serviceDate->setTime(
                    $serviceStartTime->hour,
                    $serviceStartTime->minute,
                    $serviceStartTime->second,
                );
            }

            $hoursUntilService = now()->diffInHours($serviceDate, false);

            // Determine refund amount based on cancellation policy
            $refundAmount = 0;
            $refundPercentage = 0;
            $payment = $booking->payment;

            if ($payment && $payment->isSucceeded()) {
                $fullRefundHours = config('marketplace.cancellation.full_refund_hours_before');
                $partialRefundPercentage = config('marketplace.cancellation.partial_refund_percentage');
                $noRefundHours = config('marketplace.cancellation.no_refund_hours_before');

                if ($hoursUntilService >= $fullRefundHours) {
                    // Full refund
                    $refundPercentage = 100;
                    $refundAmount = $payment->amount;
                } elseif ($hoursUntilService >= $noRefundHours) {
                    // Partial refund
                    $refundPercentage = $partialRefundPercentage;
                    $refundAmount = round($payment->amount * ($partialRefundPercentage / 100), 2);
                }
                // else: No refund

                // Process refund if applicable
                if ($refundAmount > 0) {
                    $this->stripeService->refundPayment($payment, $refundAmount);

                    Log::info('Refund processed', [
                        'booking_id' => $booking->id,
                        'refund_amount' => $refundAmount,
                        'refund_percentage' => $refundPercentage,
                    ]);
                }
            }

            // Cancel the booking
            $booking->cancel($reason);

            // If service request was filled, mark it back as open if cancellation is early enough
            if ($serviceRequest->isFilled() && $hoursUntilService > config('marketplace.booking_windows.min_lead_time_hours')) {
                $serviceRequest->update(['status' => 'open']);
            }

            // Cancel any scheduled payouts for this booking
            $payout = $booking->payout;
            if ($payout && $payout->isScheduled()) {
                $payout->update(['status' => 'cancelled']);
            }

            DB::commit();

            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'reason' => $reason,
                'refund_percentage' => $refundPercentage,
                'hours_until_service' => $hoursUntilService,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
