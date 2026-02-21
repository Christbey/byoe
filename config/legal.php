<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legal Entity Details
    |--------------------------------------------------------------------------
    |
    | These values are injected into legal document pages. Update them in
    | your .env file rather than editing this file directly.
    |
    */
    'company_name' => env('LEGAL_COMPANY_NAME', 'ShiftFinder, Inc.'),
    'company_email' => env('LEGAL_COMPANY_EMAIL', 'legal@shiftfinder.com'),
    'governing_state' => env('LEGAL_GOVERNING_STATE', 'Kansas'),
    'effective_date' => env('LEGAL_EFFECTIVE_DATE', 'February 1, 2026'),
];
