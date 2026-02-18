<?php

/*
|--------------------------------------------------------------------------
| US State Minimum Wages (2025)
|--------------------------------------------------------------------------
|
| Hourly minimum wage rates by US state (two-letter abbreviation).
| States with no state law default to the federal minimum wage of $7.25/hr.
| These are used to enforce a price floor on service requests based on
| the shop location's state.
|
*/

return [
    'federal' => 7.25,

    'by_state' => [
        'AL' => 7.25,
        'AK' => 11.91,
        'AZ' => 14.70,
        'AR' => 11.00,
        'CA' => 16.50,
        'CO' => 14.81,
        'CT' => 16.35,
        'DE' => 15.00,
        'DC' => 17.50,
        'FL' => 14.00,
        'GA' => 7.25,
        'HI' => 14.00,
        'ID' => 7.25,
        'IL' => 15.00,
        'IN' => 7.25,
        'IA' => 7.25,
        'KS' => 7.25,
        'KY' => 7.25,
        'LA' => 7.25,
        'ME' => 14.65,
        'MD' => 15.00,
        'MA' => 15.00,
        'MI' => 10.56,
        'MN' => 10.85,
        'MS' => 7.25,
        'MO' => 12.30,
        'MT' => 10.55,
        'NE' => 13.50,
        'NV' => 12.00,
        'NH' => 7.25,
        'NJ' => 15.49,
        'NM' => 12.00,
        'NY' => 16.00,
        'NC' => 7.25,
        'ND' => 7.25,
        'OH' => 10.70,
        'OK' => 7.25,
        'OR' => 14.70,
        'PA' => 7.25,
        'RI' => 14.00,
        'SC' => 7.25,
        'SD' => 11.50,
        'TN' => 7.25,
        'TX' => 7.25,
        'UT' => 7.25,
        'VT' => 14.01,
        'VA' => 12.41,
        'WA' => 16.66,
        'WV' => 8.75,
        'WI' => 7.25,
        'WY' => 7.25,
    ],
];
