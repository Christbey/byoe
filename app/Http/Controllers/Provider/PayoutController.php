<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return Inertia::render('provider/Payouts', [
                'needsProfile' => true,
                'payouts' => [],
                'filter' => 'all',
                'stats' => [
                    'totalEarnings' => 0,
                    'pendingPayouts' => 0,
                    'thisMonthEarnings' => 0,
                ],
            ]);
        }

        $filter = $request->query('filter', 'all');

        // Build query for payouts
        $query = Payout::with([
            'booking.serviceRequest.shopLocation.shop',
        ])
            ->where('provider_id', $provider->id)
            ->when($filter !== 'all', function ($query) use ($filter) {
                $query->where('status', $filter);
            })
            ->orderBy('created_at', 'desc');

        $payouts = $query->paginate(15);

        // Calculate stats
        $totalEarnings = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayouts = Payout::where('provider_id', $provider->id)
            ->where('status', 'pending')
            ->sum('amount');

        $thisMonthEarnings = Payout::where('provider_id', $provider->id)
            ->where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return Inertia::render('provider/Payouts', [
            'needsProfile' => false,
            'payouts' => $payouts,
            'filter' => $filter,
            'stripeReady' => $provider->stripeAccount?->isFullyOnboarded() ?? false,
            'stats' => [
                'totalEarnings' => round((float) $totalEarnings, 2),
                'pendingPayouts' => round((float) $pendingPayouts, 2),
                'thisMonthEarnings' => round((float) $thisMonthEarnings, 2),
            ],
        ]);
    }
}
