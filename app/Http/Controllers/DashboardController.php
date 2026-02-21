<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\ServiceRequest;
use App\Models\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole('provider') && ! $user->hasRole(['shop_owner', 'shop_manager', 'admin'])) {
            return $this->providerDashboard($request);
        }

        if ($user->hasRole(['shop_owner', 'shop_manager'])) {
            return $this->shopDashboard($request);
        }

        if ($user->hasRole('admin')) {
            // Admins see shop dashboard by default (they can switch via sidebar)
            return $this->shopDashboard($request);
        }

        // Fallback for users with no specific role
        return Inertia::render('Dashboard', [
            'view' => 'default',
        ]);
    }

    private function providerDashboard(Request $request): Response
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return Inertia::render('Dashboard', [
                'view' => 'provider',
                'needsProfile' => true,
                'stats' => [
                    'earnings_this_month' => 0,
                    'earnings_this_week' => 0,
                    'total_earnings' => 0,
                    'upcoming_bookings' => 0,
                    'completed_bookings' => 0,
                    'average_rating' => 0,
                ],
                'available_requests' => [],
                'recent_activity' => [],
            ]);
        }

        $earningsThisMonth = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $earningsThisWeek = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfWeek())
            ->sum('amount');

        $totalEarnings = Booking::where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->sum('provider_payout');

        $upcomingBookingsCount = Booking::where('provider_id', $provider->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('serviceRequest', function ($query) {
                $query->where('service_date', '>=', now());
            })
            ->count();

        $completedBookingsCount = Booking::where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->count();

        // Available service requests in the area
        $availableRequests = ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->where('service_date', '>=', now())
            ->with(['shopLocation.shop'])
            ->orderBy('service_date')
            ->limit(10)
            ->get();

        $recentActivity = Booking::with(['serviceRequest.shopLocation.shop'])
            ->where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'view' => 'provider',
            'needsProfile' => false,
            'stats' => [
                'earnings_this_month' => round((float) $earningsThisMonth, 2),
                'earnings_this_week' => round((float) $earningsThisWeek, 2),
                'total_earnings' => round((float) $totalEarnings, 2),
                'upcoming_bookings' => $upcomingBookingsCount,
                'completed_bookings' => $completedBookingsCount,
                'average_rating' => round((float) $provider->average_rating, 1),
            ],
            'available_requests' => $availableRequests,
            'recent_activity' => $recentActivity,
        ]);
    }

    private function shopDashboard(Request $request): Response
    {
        $shop = $request->user()->shop;

        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::first();
        }

        if (! $shop) {
            return Inertia::render('Dashboard', [
                'view' => 'shop',
                'stats' => [
                    'active_requests' => 0,
                    'upcoming_bookings' => 0,
                    'total_spent' => 0,
                ],
                'recent_requests' => [],
                'upcoming_bookings' => [],
            ]);
        }

        $locationIds = $shop->locations()->pluck('id');

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

        $recentRequests = ServiceRequest::with(['shopLocation'])
            ->whereIn('shop_location_id', $locationIds)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

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

        return Inertia::render('Dashboard', [
            'view' => 'shop',
            'stats' => [
                'active_requests' => $activeRequestsCount,
                'upcoming_bookings' => $upcomingBookingsCount,
                'total_spent' => round((float) $totalSpent, 2),
            ],
            'recent_requests' => $recentRequests,
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}
