<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\ShopLocation;
use Illuminate\Http\JsonResponse;

class CreateServiceRequestController extends Controller
{
    public function __invoke(StoreServiceRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole(['shop_owner', 'admin'])) {
            return response()->json([
                'message' => 'You do not have permission to create service requests for this shop.',
            ], 403);
        }

        $shopLocation = ShopLocation::findOrFail($request->validated('shop_location_id'));

        if (! $user->hasRole('admin') && $shopLocation->shop->user_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to create service requests for this shop.',
            ], 403);
        }

        $validated = $request->validated();

        $serviceRequest = ServiceRequest::create([
            'shop_location_id' => $shopLocation->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'skills_required' => $validated['skills_required'] ?? [],
            'service_date' => $validated['service_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'price' => $validated['price'],
            'status' => 'open',
            'expires_at' => now()->addHours((int) config('marketplace.service_request.expiration_hours', config('marketplace.service_request_expiration_hours', 72))),
        ]);

        return response()->json([
            'message' => 'Service request created successfully.',
            'service_request' => $serviceRequest,
        ], 201);
    }
}
