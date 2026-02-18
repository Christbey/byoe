<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        // Get the authenticated user's shop
        $shop = $request->user()->shop;

        // If user doesn't have a shop yet (admin viewing), use the first available shop
        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::first();
        }

        if (! $shop) {
            // User has no shop yet, return empty dashboard
            return Inertia::render('shop/Dashboard', [
                'stats' => [
                    'active_requests' => 0,
                    'upcoming_bookings' => 0,
                    'total_spent' => 0,
                ],
                'recent_requests' => [],
                'upcoming_bookings' => [],
            ]);
        }

        // Get shop location IDs for queries
        $locationIds = $shop->locations()->pluck('id');

        // Calculate stats
        $activeRequestsCount = ServiceRequest::whereIn('shop_location_id', $locationIds)
            ->where('status', 'open')
            ->count();

        $upcomingBookingsCount = Booking::whereHas('serviceRequest', function ($query) use ($locationIds) {
            $query->whereIn('shop_location_id', $locationIds)
                ->where('service_date', '>=', now())
                ->where('service_date', '<=', now()->addDays(7));
        })
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $totalSpent = Payment::whereHas('booking.serviceRequest', function ($query) use ($locationIds) {
            $query->whereIn('shop_location_id', $locationIds);
        })
            ->where('status', 'succeeded')
            ->sum('amount');

        // Get recent service requests (last 5)
        $recentRequests = ServiceRequest::with(['shopLocation'])
            ->whereIn('shop_location_id', $locationIds)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get upcoming bookings (next 7 days)
        $upcomingBookings = Booking::with(['serviceRequest.shopLocation', 'provider.user'])
            ->whereHas('serviceRequest', function ($query) use ($locationIds) {
                $query->whereIn('shop_location_id', $locationIds)
                    ->where('service_date', '>=', now())
                    ->where('service_date', '<=', now()->addDays(7));
            })
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();

        return Inertia::render('shop/Dashboard', [
            'stats' => [
                'active_requests' => $activeRequestsCount,
                'upcoming_bookings' => $upcomingBookingsCount,
                'total_spent' => $totalSpent / 100, // Convert cents to dollars
            ],
            'recent_requests' => $recentRequests,
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}
