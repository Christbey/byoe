<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Fee Configuration
    |--------------------------------------------------------------------------
    |
    | The percentage fee charged by the platform on each transaction.
    | This is deducted from the service price before paying out to providers.
    |
    */
    'platform_fee_percentage' => env('MARKETPLACE_PLATFORM_FEE_PERCENTAGE', 15.0),

    /*
    |--------------------------------------------------------------------------
    | Booking Windows
    |--------------------------------------------------------------------------
    |
    | Time windows for booking-related operations.
    |
    */
    'booking_windows' => [
        // Minimum hours before service date that a booking can be made
        'min_lead_time_hours' => env('MARKETPLACE_MIN_LEAD_TIME_HOURS', 4),

        // Maximum hours in advance that a booking can be made
        'max_lead_time_hours' => env('MARKETPLACE_MAX_LEAD_TIME_HOURS', 2160), // 90 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Request Expiration
    |--------------------------------------------------------------------------
    |
    | How long service requests remain open before expiring.
    |
    */
    'service_request_expiration_hours' => env('MARKETPLACE_SERVICE_REQUEST_EXPIRATION_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy
    |--------------------------------------------------------------------------
    |
    | Refund percentages based on when cancellation occurs.
    |
    */
    'cancellation' => [
        // Hours before service for full refund
        'full_refund_hours_before' => env('MARKETPLACE_FULL_REFUND_HOURS', 48),

        // Hours before service for partial refund
        'partial_refund_hours_before' => env('MARKETPLACE_PARTIAL_REFUND_HOURS', 24),

        // Percentage refunded for partial refund
        'partial_refund_percentage' => env('MARKETPLACE_PARTIAL_REFUND_PERCENTAGE', 50),

        // Minimum hours before service to allow cancellation with any refund
        'no_refund_hours_before' => env('MARKETPLACE_NO_REFUND_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payout Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for provider payouts.
    |
    */
    'payout' => [
        // Days to wait after booking completion before releasing payout
        'auto_payout_delay_days' => env('MARKETPLACE_PAYOUT_DELAY_DAYS', 3),

        // Minimum payout amount (in cents)
        'minimum_payout_amount' => env('MARKETPLACE_MIN_PAYOUT_AMOUNT', 1000), // $10.00
    ],

    /*
    |--------------------------------------------------------------------------
    | Rating System
    |--------------------------------------------------------------------------
    |
    | Configuration for the two-sided rating system.
    |
    */
    'ratings' => [
        // Allow ratings this many days after booking completion
        'rating_window_days' => env('MARKETPLACE_RATING_WINDOW_DAYS', 14),

        // Minimum rating (1-5 scale)
        'min_rating' => 1,

        // Maximum rating (1-5 scale)
        'max_rating' => 5,
    ],
];
