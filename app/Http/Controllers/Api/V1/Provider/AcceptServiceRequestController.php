<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Actions\AcceptServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * @group Provider
 *
 * APIs for providers to manage their bookings and accept service requests.
 */
class AcceptServiceRequestController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AcceptServiceRequestAction $acceptAction
    ) {}

    /**
     * Accept service request
     *
     * Accept an open service request and create a booking. Provider must be active and have completed Stripe onboarding.
     *
     * @authenticated
     *
     * @urlParam serviceRequest integer required The service request ID. Example: 1
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "status": "confirmed",
     *     "service_request": {
     *       "id": 1,
     *       "title": "Barista needed"
     *     }
     *   }
     * }
     */
    public function __invoke(int|string $serviceRequest): JsonResponse
    {
        // Manually resolve service request
        $serviceRequest = ServiceRequest::findOrFail($serviceRequest);

        $provider = auth()->user()->provider;

        if (! $provider) {
            return response()->json([
                'message' => 'Provider profile not found.',
            ], 403);
        }

        Gate::authorize('accept', $serviceRequest);

        // Validate provider is ready
        if (! $provider->is_active) {
            return response()->json([
                'message' => 'Provider account is not active.',
            ], 403);
        }

        if (! $provider->canReceivePayouts()) {
            return response()->json([
                'message' => 'Complete Stripe onboarding before accepting requests.',
            ], 403);
        }

        try {
            // USES EXISTING ACTION - delegates to BookingService
            $booking = ($this->acceptAction)($serviceRequest, $provider);

            return BookingResource::make($booking->load(['serviceRequest', 'provider.user']))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Failed to accept service request via API', [
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
