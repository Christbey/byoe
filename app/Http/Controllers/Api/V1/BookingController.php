<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateRatingAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Http\Resources\V1\RatingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * @group Bookings
 *
 * APIs for managing bookings (accepted service requests). Providers and shop owners can view, complete, cancel, and rate bookings.
 */
class BookingController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected BookingService $bookingService,
        protected CreateRatingAction $createRatingAction,
        protected StripeService $stripeService
    ) {}

    /**
     * List bookings
     *
     * Returns a paginated list of bookings filtered by user role. Shop owners see their bookings, providers see their accepted bookings.
     *
     * @authenticated
     *
     * @queryParam status string Filter by status (pending, confirmed, completed, cancelled). Example: confirmed
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        $query = Booking::query()
            ->with(['serviceRequest.shopLocation.shop', 'provider.user', 'payment', 'payout', 'ratings']);

        // Filter by user role
        if ($request->user()->hasRole('provider')) {
            $query->where('provider_id', $request->user()->provider?->id);
        } elseif ($request->user()->hasRole('shop_owner')) {
            $query->whereHas('serviceRequest.shopLocation.shop', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return BookingResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }

    /**
     * Get booking details
     *
     * Retrieve detailed information about a specific booking including service request, provider, payments, and ratings.
     *
     * @authenticated
     *
     * @urlParam booking integer required The booking ID. Example: 1
     */
    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        $booking->load(['serviceRequest.shopLocation.shop', 'provider.user', 'payment', 'payout', 'ratings.rater', 'ratings.ratee']);

        return BookingResource::make($booking);
    }

    /**
     * Complete a booking
     *
     * Mark a booking as completed and trigger payout processing to the provider.
     *
     * @authenticated
     *
     * @urlParam booking integer required The booking ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "status": "completed"
     *   }
     * }
     */
    public function complete(int|string $booking): JsonResponse
    {
        // Manually resolve booking since route model binding isn't working in tests
        $booking = Booking::findOrFail($booking);

        $booking->loadMissing(['serviceRequest.shopLocation.shop', 'provider.user']);

        Gate::authorize('complete', $booking);

        try {
            // USES EXISTING SERVICE - same as web
            $this->bookingService->completeBooking($booking);

            return BookingResource::make($booking->fresh())
                ->response();
        } catch (\Exception $e) {
            Log::error('Failed to complete booking via API', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a booking
     *
     * Cancel a booking and initiate a refund if applicable.
     *
     * @authenticated
     *
     * @urlParam booking integer required The booking ID. Example: 1
     *
     * @bodyParam reason string required Reason for cancellation. Example: Provider is no longer available
     */
    public function cancel(Request $request, int|string $booking): JsonResponse
    {
        // Manually resolve booking
        $booking = Booking::findOrFail($booking);

        $booking->loadMissing(['serviceRequest.shopLocation.shop', 'provider.user']);

        Gate::authorize('delete', $booking);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            // USES EXISTING SERVICE - same refund logic as web
            $this->bookingService->cancelBooking($booking, $validated['reason']);

            return BookingResource::make($booking->fresh())
                ->response();
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking via API', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rate a booking
     *
     * Submit a rating for a completed booking. Shop owners can rate providers and vice versa.
     *
     * @authenticated
     *
     * @urlParam booking integer required The booking ID. Example: 1
     *
     * @bodyParam rating integer required Rating from 1 to 5 stars. Example: 5
     * @bodyParam comment string Optional comment about the rating. Example: Excellent service!
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "rating": 5,
     *     "comment": "Excellent service!"
     *   }
     * }
     */
    public function rate(Request $request, int|string $booking): JsonResponse
    {
        // Manually resolve booking
        $booking = Booking::findOrFail($booking);

        $booking->loadMissing(['serviceRequest.shopLocation.shop', 'provider.user']);

        Gate::authorize('rate', $booking);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            // Check if user already rated this booking
            $existingRating = $booking->ratings()
                ->where('rater_id', $request->user()->id)
                ->first();

            if ($existingRating) {
                return response()->json([
                    'message' => 'You have already rated this booking.',
                ], 422);
            }

            // USES EXISTING ACTION - same as web
            $rating = ($this->createRatingAction)(
                $booking,
                $request->user(),
                $validated
            );

            return RatingResource::make($rating->load(['rater', 'ratee']))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Failed to rate booking via API', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create rating.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create payment intent
     *
     * Create a Stripe payment intent for a booking. Shop owners use this to initiate payment for confirmed bookings.
     *
     * @authenticated
     *
     * @urlParam booking integer required The booking ID. Example: 1
     *
     * @response 200 {
     *   "client_secret": "pi_xxx_secret_xxx",
     *   "amount": 15000,
     *   "payment_id": 1
     * }
     */
    public function createPaymentIntent(Request $request, int|string $booking): JsonResponse
    {
        // Manually resolve booking
        $booking = Booking::findOrFail($booking);

        try {
            // Ensure booking belongs to authenticated user's shop
            $user = $request->user();
            $shop = $user->shop;

            if (! $shop) {
                return response()->json([
                    'message' => 'Shop not found for this user.',
                ], 403);
            }

            $booking->load('serviceRequest.shopLocation');

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
