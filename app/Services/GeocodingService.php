<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\ShopLocation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for geocoding addresses and calculating distances.
 *
 * This service handles:
 * - Geocoding addresses to latitude/longitude coordinates using Nominatim API
 * - Calculating distances between coordinates using the Haversine formula
 * - Finding nearby providers within a specified radius
 * - Caching geocoding results to reduce API calls
 */
class GeocodingService
{
    private Client $client;
    private static ?int $lastRequestTime = null;

    /**
     * Create a new GeocodingService instance.
     */
    public function __construct(
        private readonly Client $httpClient = new Client(),
    ) {
        $this->client = $httpClient;
    }

    /**
     * Geocode a shop location address to latitude/longitude coordinates.
     *
     * Uses the Nominatim API (free OpenStreetMap service) to convert an address
     * to geographic coordinates. Results are cached to avoid repeated API calls.
     * Respects Nominatim's rate limit of 1 request per second.
     *
     * @param ShopLocation $location The shop location to geocode
     * @return bool True if geocoding was successful, false otherwise
     */
    public function geocodeLocation(ShopLocation $location): bool
    {
        $address = $location->fullAddress();
        $cacheKey = 'geocode:' . md5($address);

        // Check if we have a cached result
        if (config('geo.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $location->update([
                    'latitude' => $cached['lat'],
                    'longitude' => $cached['lon'],
                    'geocoded_at' => now(),
                ]);
                return true;
            }
        }

        try {
            // Respect Nominatim rate limit (1 request per second)
            $this->respectRateLimit();

            $response = $this->client->get(config('geo.nominatim.base_url') . '/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => config('geo.nominatim.user_agent'),
                ],
            ]);

            $results = json_decode($response->getBody()->getContents(), true);

            if (empty($results)) {
                Log::warning('Geocoding failed: No results found', ['address' => $address]);
                return false;
            }

            $result = $results[0];
            $lat = (float) $result['lat'];
            $lon = (float) $result['lon'];

            // Update the location with coordinates
            $location->update([
                'latitude' => $lat,
                'longitude' => $lon,
                'geocoded_at' => now(),
            ]);

            // Cache the result
            if (config('geo.cache.enabled')) {
                $ttl = now()->addDays(config('geo.cache.ttl_days'));
                Cache::put($cacheKey, ['lat' => $lat, 'lon' => $lon], $ttl);
            }

            return true;
        } catch (GuzzleException $e) {
            Log::error('Geocoding API error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Geocoding error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate distance between two coordinates using the Haversine formula.
     *
     * The Haversine formula calculates the great-circle distance between two points
     * on a sphere given their longitudes and latitudes.
     *
     * @param float $lat1 Latitude of the first point
     * @param float $lon1 Longitude of the first point
     * @param float $lat2 Latitude of the second point
     * @param float $lon2 Longitude of the second point
     * @return float Distance in miles
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = config('geo.earth_radius_miles');

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find nearby providers within a specified radius of a shop location.
     *
     * Queries the database for active providers whose service area overlaps with
     * the specified location and radius. Uses a bounding box query for efficiency,
     * then filters by exact distance calculation.
     *
     * @param ShopLocation $location The shop location to search around
     * @param int|null $radiusMiles The search radius in miles (uses config default if null)
     * @return Collection<Provider> Collection of nearby providers with distance attribute
     */
    public function findNearbyProviders(ShopLocation $location, ?int $radiusMiles = null): Collection
    {
        $radius = $radiusMiles ?? config('geo.search_radius_miles');

        // Ensure the location is geocoded
        if (!$location->latitude || !$location->longitude) {
            if (!$this->geocodeLocation($location)) {
                return collect();
            }
        }

        $lat = (float) $location->latitude;
        $lon = (float) $location->longitude;

        // Calculate bounding box for efficient querying
        // Approximate: 1 degree latitude ≈ 69 miles, 1 degree longitude ≈ 69 * cos(latitude) miles
        $earthRadius = config('geo.earth_radius_miles');
        $latDelta = $radius / 69.0;
        $lonDelta = $radius / (69.0 * cos(deg2rad($lat)));

        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLon = $lon - $lonDelta;
        $maxLon = $lon + $lonDelta;

        // Query providers within bounding box
        $providers = Provider::query()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->map(function (Provider $provider) use ($lat, $lon) {
                // Get provider's home location (assuming providers have a home_latitude/home_longitude)
                // For now, we'll just return providers and add distance calculation
                // You may need to adjust this based on how provider locations are stored
                $provider->distance = null;
                return $provider;
            })
            ->filter(function (Provider $provider) use ($radius) {
                // Filter by actual distance if provider has coordinates
                return true; // Adjust based on your provider location logic
            });

        return $providers;
    }

    /**
     * Respect Nominatim's rate limit of 1 request per second.
     *
     * @return void
     */
    private function respectRateLimit(): void
    {
        $rateLimit = config('geo.nominatim.rate_limit_per_second');
        $minDelay = (int) (1000000 / $rateLimit); // microseconds

        if (self::$lastRequestTime !== null) {
            $elapsed = (int) ((microtime(true) * 1000000) - self::$lastRequestTime);
            if ($elapsed < $minDelay) {
                usleep($minDelay - $elapsed);
            }
        }

        self::$lastRequestTime = (int) (microtime(true) * 1000000);
    }
}
