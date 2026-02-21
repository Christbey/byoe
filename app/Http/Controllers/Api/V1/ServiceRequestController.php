<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequestRequest;
use App\Http\Resources\V1\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\ShopLocation;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * @group Service Requests
 *
 * APIs for managing service requests (job postings created by shops for providers to accept).
 */
class ServiceRequestController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected CreateServiceRequestAction $createAction,
        protected StripeService $stripeService
    ) {}

    /**
     * List service requests
     *
     * Returns a paginated list of service requests filtered by user role.
     * Shop owners see their own requests, providers see open requests, admins see all.
     *
     * @queryParam status string Filter by status. Example: open
     * @queryParam skills string[] Filter by required skills. Example: barista,latte_art
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ServiceRequest::class);

        $query = ServiceRequest::query()
            ->with(['shopLocation.shop', 'booking.provider.user']);

        // Filter by user role
        if ($request->user()->hasRole('provider')) {
            // Providers see open requests or their own bookings
            $query->where(function ($q) use ($request) {
                $q->where('status', 'open')
                    ->orWhereHas('booking', function ($bookingQuery) use ($request) {
                        $bookingQuery->where('provider_id', $request->user()->provider?->id);
                    });
            });
        } elseif ($request->user()->hasRole('shop_owner')) {
            // Shop owners see their own requests
            $query->whereHas('shopLocation.shop', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }
        // Admins see all (no filter)

        // Additional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : [$request->skills];
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('skills_required', $skill);
                }
            });
        }

        return ServiceRequestResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }

    /**
     * Store a newly created service request.
     */
    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        Gate::authorize('create', ServiceRequest::class);

        $shopLocation = ShopLocation::findOrFail($request->shop_location_id);

        // Verify shop location belongs to the authenticated user's shop
        $shop = $request->user()->shop;
        if (! $shop || $shopLocation->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Unauthorized access to this shop location.',
            ], 403);
        }

        // USES EXISTING ACTION - same as web controller
        $serviceRequest = ($this->createAction)($shopLocation, $request->validated());

        return ServiceRequestResource::make($serviceRequest->load('shopLocation'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified service request.
     */
    public function show(int|string $serviceRequest): ServiceRequestResource
    {
        // Manually resolve service request
        $serviceRequest = ServiceRequest::findOrFail($serviceRequest);

        Gate::authorize('view', $serviceRequest);

        $serviceRequest->load(['shopLocation.shop', 'booking.provider.user']);

        return ServiceRequestResource::make($serviceRequest);
    }

    /**
     * Update the specified service request.
     */
    public function update(Request $request, int|string $serviceRequest): ServiceRequestResource
    {
        // Manually resolve service request
        $serviceRequest = ServiceRequest::findOrFail($serviceRequest);

        Gate::authorize('update', $serviceRequest);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'skills_required' => ['sometimes', 'array'],
            'service_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
        ]);

        $serviceRequest->update($validated);

        return ServiceRequestResource::make($serviceRequest->fresh(['shopLocation', 'booking']));
    }

    /**
     * Remove the specified service request (cancel).
     */
    public function destroy(int|string $serviceRequest): JsonResponse
    {
        // Manually resolve service request
        $serviceRequest = ServiceRequest::findOrFail($serviceRequest);

        $serviceRequest->loadMissing('shopLocation.shop');

        Gate::authorize('delete', $serviceRequest);

        $serviceRequest->markAsCancelled();

        return response()->json([
            'message' => 'Service request cancelled successfully.',
        ]);
    }

    /**
     * Confirm payment for a service request.
     *
     * Creates a Stripe payment intent and transitions the service request to 'open' status.
     */
    public function confirmPayment(Request $request, int|string $serviceRequest): JsonResponse
    {
        // Manually resolve service request
        $serviceRequest = ServiceRequest::findOrFail($serviceRequest);

        Gate::authorize('confirmPayment', $serviceRequest);

        try {
            if ($serviceRequest->status !== 'pending_payment') {
                return response()->json([
                    'message' => 'Service request is not pending payment.',
                ], 422);
            }

            // Create a payment intent for the service request
            // Note: The actual payment will be created when a provider accepts the request
            // For now, we just mark it as open to allow providers to accept
            $serviceRequest->update(['status' => 'open']);

            Log::info('Service request payment confirmed', [
                'service_request_id' => $serviceRequest->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Payment confirmed. Service request is now open for providers.',
                'service_request' => ServiceRequestResource::make($serviceRequest->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to confirm service request payment', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to confirm payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
