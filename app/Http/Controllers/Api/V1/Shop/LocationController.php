<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ShopLocationResource;
use App\Models\ShopLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Shop
 *
 * APIs for shop owners to manage their locations and service requests.
 */
class LocationController extends Controller
{
    /**
     * List shop locations
     *
     * Returns all locations for the authenticated shop owner's shop.
     *
     * @authenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return ShopLocationResource::collection(collect());
        }

        return ShopLocationResource::collection(
            $shop->locations()->get()
        );
    }

    /**
     * Create shop location
     *
     * Add a new location to the shop. First location is automatically set as primary.
     *
     * @authenticated
     *
     * @bodyParam address_line_1 string required Street address. Example: 123 Main St
     * @bodyParam address_line_2 string Optional additional address info. Example: Suite 100
     * @bodyParam city string required City name. Example: San Francisco
     * @bodyParam state string required Two-letter state code. Example: CA
     * @bodyParam zip_code string required ZIP code. Example: 94102
     * @bodyParam is_primary boolean Whether this is the primary location. Example: true
     */
    public function store(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found for this user.',
            ], 403);
        }

        $validated = $request->validate([
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'max:10'],
            'is_primary' => ['boolean'],
        ]);

        // If this is the first location, make it primary
        if ($shop->locations()->count() === 0) {
            $validated['is_primary'] = true;
        }

        // If setting as primary, unset other primary locations
        if ($validated['is_primary'] ?? false) {
            $shop->locations()->update(['is_primary' => false]);
        }

        $location = $shop->locations()->create($validated);

        return ShopLocationResource::make($location)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get location details
     *
     * Retrieve details for a specific shop location.
     *
     * @authenticated
     *
     * @urlParam location integer required The location ID. Example: 1
     */
    public function show(Request $request, int|string $location): ShopLocationResource|JsonResponse
    {
        // Manually resolve location
        $location = ShopLocation::findOrFail($location);

        $shop = $request->user()->shop;

        if (! $shop || $location->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Unauthorized access to this location.',
            ], 403);
        }

        return ShopLocationResource::make($location);
    }

    /**
     * Update shop location
     *
     * Modify an existing shop location's details.
     *
     * @authenticated
     *
     * @urlParam location integer required The location ID. Example: 1
     *
     * @bodyParam address_line_1 string Street address. Example: 456 Market St
     * @bodyParam address_line_2 string Additional address info. Example: Floor 2
     * @bodyParam city string City name. Example: Oakland
     * @bodyParam state string Two-letter state code. Example: CA
     * @bodyParam zip_code string ZIP code. Example: 94607
     * @bodyParam is_primary boolean Whether this is the primary location. Example: false
     */
    public function update(Request $request, int|string $location): ShopLocationResource|JsonResponse
    {
        // Manually resolve location
        $location = ShopLocation::findOrFail($location);

        $location->loadMissing('shop');
        $shop = $request->user()->shop;

        if (! $shop || $location->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Unauthorized access to this location.',
            ], 403);
        }

        $validated = $request->validate([
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'size:2'],
            'zip_code' => ['sometimes', 'string', 'max:10'],
            'is_primary' => ['boolean'],
        ]);

        // If setting as primary, unset other primary locations
        if (($validated['is_primary'] ?? false) && ! $location->is_primary) {
            $shop->locations()->where('id', '!=', $location->id)->update(['is_primary' => false]);
        }

        $location->update($validated);

        return ShopLocationResource::make($location->fresh());
    }

    /**
     * Delete shop location
     *
     * Remove a shop location. Cannot delete locations with existing service requests.
     *
     * @authenticated
     *
     * @urlParam location integer required The location ID. Example: 1
     */
    public function destroy(Request $request, int|string $location): JsonResponse
    {
        // Manually resolve location
        $location = ShopLocation::findOrFail($location);

        $location->loadMissing('shop');
        $shop = $request->user()->shop;

        if (! $shop || $location->shop_id !== $shop->id) {
            return response()->json([
                'message' => 'Unauthorized access to this location.',
            ], 403);
        }

        // Prevent deletion if there are service requests for this location
        if ($location->serviceRequests()->exists()) {
            return response()->json([
                'message' => 'Cannot delete location with existing service requests.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully.',
        ]);
    }
}
