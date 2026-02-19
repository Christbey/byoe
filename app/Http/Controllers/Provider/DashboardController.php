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
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $earningsAllTime = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->sum('amount');

        // Upcoming bookings list
        $upcomingBookings = Booking::with(['serviceRequest.shopLocation.shop'])
            ->join('service_requests', 'service_requests.id', '=', 'bookings.service_request_id')
            ->where('bookings.provider_id', $provider->id)
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->where('service_requests.service_date', '>=', now())
            ->orderBy('service_requests.service_date')
            ->select('bookings.*')
            ->limit(5)
            ->get();

        return Inertia::render('provider/Dashboard', [
            'earnings' => [
                'this_week' => $earningsThisWeek,
                'this_month' => $earningsThisMonth,
                'all_time' => $earningsAllTime,
            ],
            'stats' => [
                'completed_jobs' => $provider->completed_bookings,
                'average_rating' => round($provider->average_rating, 1),
                'total_earnings' => $earningsAllTime,
                'pending_invitations' => 0,
            ],
            'upcoming_bookings' => $upcomingBookings,
        ]);
    }
}
