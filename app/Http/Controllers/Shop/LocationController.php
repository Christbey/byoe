<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected GeocodingService $geocodingService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Get the authenticated user's shop
        $shop = $request->user()->shop;

        // If user doesn't have a shop yet (admin viewing), use the first available shop
        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::first();
        }

        // Get shop locations
        $locations = $shop ? $shop->locations()->orderBy('is_primary', 'desc')->orderBy('created_at', 'asc')->get() : collect();

        return Inertia::render('shop/Locations', [
            'locations' => $locations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('shop/CreateLocation');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'max:10'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Get the shop
        $shop = $request->user()->shop;

        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::first();
        }

        if (! $shop) {
            return redirect()->back()
                ->withErrors(['shop' => 'You must have a shop to create locations.'])
                ->withInput();
        }

        // If this is set as primary, unset all other primary locations
        if ($validated['is_primary'] ?? false) {
            $shop->locations()->update(['is_primary' => false]);
        }

        // If this is the first location, make it primary
        if ($shop->locations()->count() === 0) {
            $validated['is_primary'] = true;
        }

        // Create the location
        $location = $shop->locations()->create($validated);

        // Attempt to geocode the location immediately
        $this->geocodingService->geocodeLocation($location);

        return redirect()->route('shop.locations.index')
            ->with('success', 'Location created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not needed for locations - redirect to index
        return redirect()->route('shop.locations.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ShopLocation $location): Response
    {
        // Verify the user has permission to edit this location
        $user = $request->user();
        if (! $user->hasRole('admin') && $location->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to edit this location.');
        }

        return Inertia::render('shop/EditLocation', [
            'location' => $location,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShopLocation $location): RedirectResponse
    {
        // Verify the user has permission to update this location
        $user = $request->user();
        if (! $user->hasRole('admin') && $location->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to update this location.');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'max:10'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Check if address changed (need to re-geocode)
        $addressChanged =
            $location->address_line_1 !== $validated['address_line_1'] ||
            $location->address_line_2 !== ($validated['address_line_2'] ?? null) ||
            $location->city !== $validated['city'] ||
            $location->state !== $validated['state'] ||
            $location->zip_code !== $validated['zip_code'];

        // If this is set as primary, unset all other primary locations
        if ($validated['is_primary'] ?? false) {
            $location->shop->locations()->where('id', '!=', $location->id)->update(['is_primary' => false]);
        }

        // Update the location
        $location->update($validated);

        // Re-geocode if address changed
        if ($addressChanged) {
            $this->geocodingService->geocodeLocation($location);
        }

        return redirect()->route('shop.locations.index')
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ShopLocation $location): RedirectResponse
    {
        // Verify the user has permission to delete this location
        $user = $request->user();
        if (! $user->hasRole('admin') && $location->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to delete this location.');
        }

        // Prevent deleting primary location if there are other locations
        if ($location->is_primary && $location->shop->locations()->count() > 1) {
            return redirect()->back()
                ->withErrors(['location' => 'Cannot delete primary location. Set another location as primary first.']);
        }

        // Check if location has active service requests
        $hasActiveRequests = $location->serviceRequests()
            ->whereIn('status', ['open', 'filled'])
            ->exists();

        if ($hasActiveRequests) {
            return redirect()->back()
                ->withErrors(['location' => 'Cannot delete location with active service requests.']);
        }

        // Delete the location
        $location->delete();

        return redirect()->route('shop.locations.index')
            ->with('success', 'Location deleted successfully!');
    }
}
