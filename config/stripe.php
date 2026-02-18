<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | Your Stripe publishable and secret keys.
    |
    */
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret for verifying Stripe webhook requests.
    |
    */
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Connect
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe Connect Express accounts.
    |
    */
    'connect' => [
        'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),

        // Account type: 'express' or 'standard'
        'account_type' => env('STRIPE_CONNECT_ACCOUNT_TYPE', 'express'),

        // Capabilities to request for connected accounts
        'capabilities' => [
            'card_payments' => ['requested' => true],
            'transfers' => ['requested' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for payment processing.
    |
    */
    'payment' => [
        // Payment capture method: 'automatic' or 'manual'
        'capture_method' => env('STRIPE_CAPTURE_METHOD', 'automatic'),

        // Currency code
        'currency' => env('STRIPE_CURRENCY', 'usd'),

        // Statement descriptor (appears on customer's bank statement)
        'statement_descriptor' => env('STRIPE_STATEMENT_DESCRIPTOR', config('app.name')),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The Stripe API version to use.
    |
    */
    'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),
];
