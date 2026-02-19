<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailableRequestsController extends Controller
{
    public function __construct(
        protected GeocodingService $geocodingService
    ) {}

    /**
     * List available service requests for providers, with optional filtering.
     *
     * Supports four filter modes:
     * - all: all open, non-expired requests ordered by service date
     * - today: requests with service_date = today
     * - week: requests with service_date within the next 7 days
     * - nearby: requests within the provider's service area, sorted by distance
     */
    public function __invoke(Request $request): Response
    {
        $provider = $request->user()->provider;
        $filter = $request->input('filter', 'all');

        $query = ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->with(['shopLocation.shop']);

        if ($filter === 'today') {
            $query->whereDate('service_date', today());
        } elseif ($filter === 'week') {
            $query->whereBetween('service_date', [today(), today()->addDays(7)]);
        }

        $providerLat = null;
        $providerLng = null;
        $locationSource = null;

        if ($filter === 'nearby') {
            [$providerLat, $providerLng, $locationSource] = $this->resolveProviderLocation($request, $provider);

            if ($providerLat && $providerLng) {
                $radiusMiles = $provider->service_area_max_miles ?: config('geo.search_radius_miles', 25);
                $latDelta = $radiusMiles / 69.0;
                $lngDelta = $radiusMiles / (69.0 * cos(deg2rad($providerLat)));

                $query->whereHas('shopLocation', function ($q) use ($providerLat, $providerLng, $latDelta, $lngDelta) {
                    $q->whereBetween('latitude', [$providerLat - $latDelta, $providerLat + $latDelta])
                        ->whereBetween('longitude', [$providerLng - $lngDelta, $providerLng + $lngDelta]);
                });
            }
        }

        if ($filter === 'nearby' && $providerLat && $providerLng) {
            $radiusMiles = $provider->service_area_max_miles ?: config('geo.search_radius_miles', 25);
            $all = $query->get()
                ->map(function ($req) use ($providerLat, $providerLng) {
                    $loc = $req->shopLocation;
                    $req->distance = ($loc?->latitude && $loc?->longitude)
                        ? $this->geocodingService->calculateDistance($providerLat, $providerLng, $loc->latitude, $loc->longitude)
                        : null;

                    return $req;
                })
                ->filter(fn ($req) => $req->distance !== null && $req->distance <= $radiusMiles)
                ->sortBy('distance')
                ->values();

            $page = (int) $request->input('page', 1);
            $perPage = 15;
            $requests = new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, $perPage),
                $all->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $requests = $query->orderBy('service_date')->paginate(15)->withQueryString();
        }

        return Inertia::render('provider/AvailableRequests', [
            'requests' => $requests,
            'filter' => $filter,
            'locationSource' => $locationSource,
        ]);
    }

    /**
     * Resolve the provider's current location from GPS params or zip code fallback.
     *
     * @return array{0: float|null, 1: float|null, 2: string|null}
     */
    private function resolveProviderLocation(Request $request, Provider $provider): array
    {
        if ($request->filled('lat') && $request->filled('lng')) {
            return [(float) $request->input('lat'), (float) $request->input('lng'), 'gps'];
        }

        $zips = $provider->preferred_zip_codes ?? [];
        if (! empty($zips)) {
            $coords = $this->geocodingService->geocodeZip($zips[0]);
            if ($coords) {
                return [$coords['lat'], $coords['lng'], 'zip'];
            }
        }

        return [null, null, null];
    }
}
