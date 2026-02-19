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
    'company_name' => env('LEGAL_COMPANY_NAME', 'BYOE, Inc.'),
    'company_email' => env('LEGAL_COMPANY_EMAIL', 'legal@byoe.com'),
    'governing_state' => env('LEGAL_GOVERNING_STATE', 'Kansas'),
    'effective_date' => env('LEGAL_EFFECTIVE_DATE', 'February 1, 2026'),
];
