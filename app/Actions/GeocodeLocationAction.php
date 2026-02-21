<?php

namespace App\Actions;

use App\Models\ShopLocation;
use App\Services\GeocodingService;

class GeocodeLocationAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        protected GeocodingService $geocodingService
    ) {}

    /**
     * Execute the action.
     *
     * @param  ShopLocation  $location  The shop location to geocode
     * @return ShopLocation The updated shop location
     */
    public function __invoke(ShopLocation $location): ShopLocation
    {
        // Use the geocoding service to geocode the location
        $this->geocodingService->geocodeLocation($location);

        // Return the updated location (refresh from database to get latest data)
        return $location->fresh();
    }
}
