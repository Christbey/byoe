<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreatePaymentIntentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StripeService $stripeService
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Booking $booking): JsonResponse
    {
        try {
            // Ensure booking belongs to authenticated user's shop
            $user = $request->user();
            $shop = $user->shop;

            if (! $shop) {
                return response()->json([
                    'message' => 'Shop not found for this user.',
                ], 403);
            }

            // Verify booking is for this shop's service request
            if ($booking->serviceRequest->shopLocation->shop_id !== $shop->id) {
                return response()->json([
                    'message' => 'Unauthorized access to this booking.',
                ], 403);
            }

            // Check if booking is in correct state
            if ($booking->status !== 'pending') {
                return response()->json([
                    'message' => 'Booking is not in a payable state.',
                ], 422);
            }

            // Create payment intent
            $payment = $this->stripeService->createPaymentIntent($booking);

            return response()->json([
                'client_secret' => $payment->stripe_payment_intent_id,
                'amount' => $payment->amount * 100, // Convert to cents
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create payment intent.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
