<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Provider
 *
 * APIs for providers to manage their bookings and accept service requests.
 */
class BookingController extends Controller
{
    /**
     * List provider bookings
     *
     * Returns the authenticated provider's bookings with optional filtering.
     *
     * @authenticated
     *
     * @queryParam status string Filter by status. Example: confirmed
     * @queryParam filter string Filter by upcoming or past. Example: upcoming
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return BookingResource::collection(collect());
        }

        $query = $provider->bookings()
            ->with(['serviceRequest.shopLocation.shop', 'payment', 'payout', 'ratings']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by upcoming/past
        if ($request->filter === 'upcoming') {
            $query->whereIn('status', ['pending', 'confirmed'])
                ->whereHas('serviceRequest', function ($q) {
                    $q->where('service_date', '>=', now());
                });
        } elseif ($request->filter === 'past') {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        return BookingResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }

    /**
     * List available service requests
     *
     * Returns open service requests that match the provider's skills and service area preferences.
     *
     * @authenticated
     */
    public function available(Request $request): AnonymousResourceCollection
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return \App\Http\Resources\V1\ServiceRequestResource::collection(collect());
        }

        $query = \App\Models\ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->with(['shopLocation.shop']);

        // Filter by skills if provider has skills
        if (! empty($provider->skills)) {
            $query->where(function ($q) use ($provider) {
                foreach ($provider->skills as $skill) {
                    $q->orWhereJsonContains('skills_required', $skill);
                }
            });
        }

        // Filter by service area if provider has preferred zip codes
        if (! empty($provider->preferred_zip_codes)) {
            $query->whereHas('shopLocation', function ($q) use ($provider) {
                $q->whereIn('zip_code', $provider->preferred_zip_codes);
            });
        }

        return \App\Http\Resources\V1\ServiceRequestResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }
}
