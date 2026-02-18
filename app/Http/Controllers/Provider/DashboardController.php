<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->route('provider.profile.edit')
                ->with('info', 'Please complete your provider profile to get started.');
        }

        // Earnings calculations
        $earningsThisWeek = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');

        $earningsThisMonth = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $earningsAllTime = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->sum('amount');

        // Stats
        $completedBookingsCount = Booking::where('provider_id', $provider->id)
            ->where('status', 'completed')
            ->count();

        // Upcoming bookings list
        $upcomingBookings = Booking::with(['serviceRequest.shopLocation.shop'])
            ->where('provider_id', $provider->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('serviceRequest', fn ($q) => $q->where('service_date', '>=', now()))
            ->orderByHas('serviceRequest', fn ($q) => $q->orderBy('service_date'))
            ->limit(5)
            ->get();

        return Inertia::render('provider/Dashboard', [
            'earnings' => [
                'this_week' => $earningsThisWeek,
                'this_month' => $earningsThisMonth,
                'all_time' => $earningsAllTime,
            ],
            'stats' => [
                'completed_jobs' => $completedBookingsCount,
                'average_rating' => round($provider->average_rating, 1),
                'total_earnings' => $earningsAllTime,
                'pending_invitations' => 0,
            ],
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}
