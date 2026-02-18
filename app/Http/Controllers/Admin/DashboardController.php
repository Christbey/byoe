<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        // System-wide statistics
        $stats = [
            'total_users' => User::count(),
            'active_shops' => Shop::count(),
            'active_providers' => Provider::count(),
            'total_bookings' => Booking::count(),
            'platform_fees_collected' => Payment::where('status', 'succeeded')->sum('amount') / 100,
            'disputes_requiring_attention' => Dispute::where('status', 'open')->count(),
        ];

        // Recent activity — last 10 bookings + last 5 disputes merged into a flat list
        $recentBookings = Booking::with(['serviceRequest', 'provider.user'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(fn ($booking) => [
                'id' => $booking->id,
                'type' => 'booking',
                'description' => sprintf(
                    'Booking #%d created — %s',
                    $booking->id,
                    $booking->serviceRequest?->title ?? 'Service',
                ),
                'created_at' => $booking->created_at->toISOString(),
            ]);

        $recentDisputeActivity = Dispute::with(['booking'])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get()
            ->map(fn ($dispute) => [
                'id' => $dispute->id + 10000,
                'type' => 'dispute',
                'description' => sprintf('Dispute opened for Booking #%d', $dispute->booking_id),
                'created_at' => $dispute->created_at->toISOString(),
            ]);

        $recentActivity = $recentBookings->concat($recentDisputeActivity)
            ->sortByDesc('created_at')
            ->values()
            ->take(10);

        // Revenue data — platform fees by day for the last 7 days
        $revenueDays = collect(range(6, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo));

        $dailyFees = Payment::where('status', 'succeeded')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(amount) / 100 as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $revenueData = [
            'labels' => $revenueDays->map(fn ($d) => $d->format('M j'))->values()->all(),
            'values' => $revenueDays->map(fn ($d) => (float) ($dailyFees[$d->toDateString()] ?? 0))->values()->all(),
        ];

        // System health checks
        $systemHealth = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => true, // Simplified — no queue monitor installed
            'storage' => is_writable(storage_path()),
        ];

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'revenue_data' => $revenueData,
            'system_health' => $systemHealth,
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('_health_check', true, 5);

            return Cache::get('_health_check') === true;
        } catch (\Exception) {
            return false;
        }
    }
}
