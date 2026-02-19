<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EarningsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $provider = $request->user()->provider;

        $tab = $request->query('tab', 'earnings');
        $period = $request->query('period', 'all');
        $filter = $request->query('filter', 'all');

        // ── Earnings tab ──────────────────────────────────────────────────────
        $bookings = null;
        $earningsStats = $this->emptyEarningsStats();

        if ($tab === 'earnings') {
            $query = Booking::with(['serviceRequest.shopLocation.shop', 'payout'])
                ->where('provider_id', $provider->id)
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc');

            $query = match ($period) {
                'week' => $query->where('completed_at', '>=', now()->startOfWeek()),
                'month' => $query->where('completed_at', '>=', now()->startOfMonth()),
                'quarter' => $query->where('completed_at', '>=', now()->startOfQuarter()),
                'year' => $query->where('completed_at', '>=', now()->startOfYear()),
                default => $query,
            };

            $bookings = $query->paginate(20);

            $statsQuery = Booking::where('provider_id', $provider->id)->where('status', 'completed');
            $statsQuery = match ($period) {
                'week' => $statsQuery->where('completed_at', '>=', now()->startOfWeek()),
                'month' => $statsQuery->where('completed_at', '>=', now()->startOfMonth()),
                'quarter' => $statsQuery->where('completed_at', '>=', now()->startOfQuarter()),
                'year' => $statsQuery->where('completed_at', '>=', now()->startOfYear()),
                default => $statsQuery,
            };

            $periodEarnings = $statsQuery->sum('provider_payout');
            $periodJobs = $statsQuery->count();
            $totalEarnings = Booking::where('provider_id', $provider->id)->where('status', 'completed')->sum('provider_payout');
            $totalJobs = Booking::where('provider_id', $provider->id)->where('status', 'completed')->count();

            $earningsStats = [
                'periodEarnings' => round((float) $periodEarnings, 2),
                'periodJobs' => $periodJobs,
                'totalEarnings' => round((float) $totalEarnings, 2),
                'totalJobs' => $totalJobs,
                'averagePerJob' => $totalJobs > 0 ? round($totalEarnings / $totalJobs, 2) : 0,
            ];
        }

        // ── Payouts tab ───────────────────────────────────────────────────────
        $payouts = null;
        $payoutStats = $this->emptyPayoutStats();

        if ($tab === 'payouts') {
            $payouts = Payout::with(['booking.serviceRequest.shopLocation.shop'])
                ->where('provider_id', $provider->id)
                ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $payoutStats = [
                'totalEarnings' => round((float) Payout::where('provider_id', $provider->id)->where('status', 'paid')->sum('amount'), 2),
                'pendingPayouts' => round((float) Payout::where('provider_id', $provider->id)->where('status', 'pending')->sum('amount'), 2),
                'thisMonthEarnings' => round((float) Payout::where('provider_id', $provider->id)->where('status', 'paid')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'), 2),
            ];
        }

        return Inertia::render('provider/Earnings', [
            'tab' => $tab,
            'bookings' => $bookings,
            'payouts' => $payouts,
            'earningsStats' => $earningsStats,
            'payoutStats' => $payoutStats,
            'period' => $period,
            'filter' => $filter,
            'stripeReady' => $provider->stripeAccount?->isFullyOnboarded() ?? false,
        ]);
    }

    /** @return array<string, int|float> */
    private function emptyEarningsStats(): array
    {
        return ['periodEarnings' => 0, 'periodJobs' => 0, 'totalEarnings' => 0, 'totalJobs' => 0, 'averagePerJob' => 0];
    }

    /** @return array<string, int|float> */
    private function emptyPayoutStats(): array
    {
        return ['totalEarnings' => 0, 'pendingPayouts' => 0, 'thisMonthEarnings' => 0];
    }
}
