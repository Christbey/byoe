<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IRS Form URLs
    |--------------------------------------------------------------------------
    |
    | Official IRS form URLs for provider reference.
    |
    */
    'forms' => [
        'w9' => env('IRS_W9_URL', 'https://www.irs.gov/pub/irs-pdf/fw9.pdf'),
        '1099_nec' => env('IRS_1099_NEC_URL', 'https://www.irs.gov/pub/irs-pdf/f1099nec.pdf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Threshold
    |--------------------------------------------------------------------------
    |
    | The dollar amount threshold for 1099-NEC reporting.
    | As of 2024, the threshold is $600.
    |
    */
    'reporting_threshold' => env('IRS_REPORTING_THRESHOLD', 600),

    /*
    |--------------------------------------------------------------------------
    | Tax Disclaimers
    |--------------------------------------------------------------------------
    |
    | Legal disclaimers shown to providers regarding tax obligations.
    |
    */
    'disclaimers' => [
        'w9' => 'By signing this form, you certify that the information provided is accurate and that you are not subject to backup withholding.',
        'contractor_status' => 'As an independent contractor, you are responsible for paying your own taxes. We recommend consulting with a tax professional.',
        '1099_reporting' => 'If you earn $600 or more in a calendar year, we are required to report your earnings to the IRS via Form 1099-NEC.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax ID Encryption
    |--------------------------------------------------------------------------
    |
    | Settings for encrypting SSN/EIN data.
    |
    */
    'encryption' => [
        // Show only last 4 digits in UI
        'mask_length' => 4,

        // Format for display (e.g., "***-**-1234")
        'mask_format' => '***-**-%s',
    ],
];
