<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\ServiceRequest;
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

        $provider->refreshTrustMetrics();
        $provider->load('stripeAccount');

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

        // Available service requests in the area
        $availableRequests = ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->where('service_date', '>=', now())
            ->with(['shopLocation.shop'])
            ->orderBy('service_date')
            ->limit(10)
            ->get();

        return Inertia::render('provider/Dashboard', [
            'provider' => [
                'id' => $provider->id,
                'trust_score' => $provider->trust_score,
                'reliability_score' => $provider->reliability_score,
                'vetting_status' => $provider->vetting_status,
                'trust_tier' => $provider->trust_tier,
                'trust_action_items' => $provider->trustActionItems(),
            ],
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
            'available_requests' => $availableRequests,
        ]);
    }
}
