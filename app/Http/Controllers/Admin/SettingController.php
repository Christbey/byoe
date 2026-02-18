<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        // Load platform settings from config files
        $settings = [
            'marketplace' => [
                'platform_fee_percentage' => config('marketplace.platform_fee_percentage', 15),
                'booking_window_hours' => config('marketplace.booking_window_hours', 24),
                'cancellation_window_hours' => config('marketplace.cancellation_window_hours', 4),
                'auto_payout_delay_days' => config('marketplace.auto_payout_delay_days', 3),
            ],
            'geo' => [
                'default_radius_miles' => config('geo.default_radius_miles', 25),
                'max_radius_miles' => config('geo.max_radius_miles', 100),
            ],
            'stripe' => [
                'enabled' => ! empty(config('stripe.secret_key')),
                'connect_enabled' => ! empty(config('stripe.connect_client_id')),
            ],
            'irs' => [
                'reporting_threshold' => config('irs.reporting_threshold', 600),
                'w9_form_url' => config('irs.w9_form_url'),
            ],
        ];

        return Inertia::render('admin/Settings', [
            'settings' => $settings,
        ]);
    }
}
