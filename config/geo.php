<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Geocoding Provider
    |--------------------------------------------------------------------------
    |
    | The geocoding service to use for converting addresses to coordinates.
    | Options: 'google', 'nominatim'
    |
    */
    'provider' => env('GEO_PROVIDER', 'nominatim'),

    /*
    |--------------------------------------------------------------------------
    | Google Maps API
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Maps geocoding service.
    |
    */
    'google' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nominatim (OpenStreetMap)
    |--------------------------------------------------------------------------
    |
    | Configuration for Nominatim geocoding service (free).
    |
    */
    'nominatim' => [
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('NOMINATIM_USER_AGENT', config('app.name')),
        'rate_limit_per_second' => env('NOMINATIM_RATE_LIMIT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Radius Configuration
    |--------------------------------------------------------------------------
    |
    | Default and maximum radius for location-based searches (in miles).
    |
    */
    'default_radius_miles' => env('GEO_DEFAULT_RADIUS_MILES', 25),
    'max_radius_miles' => env('GEO_MAX_RADIUS_MILES', 100),

    /*
    |--------------------------------------------------------------------------
    | Distance Calculation
    |--------------------------------------------------------------------------
    |
    | Unit for distance calculations.
    | Options: 'miles', 'kilometers'
    |
    */
    'distance_unit' => env('GEO_DISTANCE_UNIT', 'miles'),

    /*
    |--------------------------------------------------------------------------
    | Geocoding Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for caching geocoding results.
    |
    */
    'cache' => [
        'enabled' => env('GEO_CACHE_ENABLED', true),
        'ttl_days' => env('GEO_CACHE_TTL_DAYS', 30),
    ],

    'earth_radius_miles' => 3958.8,
    'search_radius_miles' => env('GEO_SEARCH_RADIUS_MILES', 25),
];
