<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListAvailableRequestsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected GeocodingService $geocodingService
    ) {
    }

    /**
     * List available service requests for providers.
     *
     * Returns paginated service requests that are:
     * - Status is 'open'
     * - Not expired
     * - Eager loads shop location and shop relationships
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Get authenticated provider
        $provider = $request->user()->provider;

        if (!$provider) {
            return response()->json([
                'message' => 'Provider profile not found.',
            ], 403);
        }

        // Query open and non-expired service requests
        $serviceRequests = ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->with([
                'shopLocation.shop',
                'shopLocation' => function ($query) {
                    $query->select('id', 'shop_id', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'latitude', 'longitude');
                },
            ])
            ->latest('created_at')
            ->paginate(15);

        return response()->json($serviceRequests);
    }
}
