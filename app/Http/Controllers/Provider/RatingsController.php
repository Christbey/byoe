<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RatingsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return Inertia::render('provider/Ratings', [
                'needsProfile' => true,
                'ratings' => [],
                'stats' => ['average' => 0, 'total' => 0, 'breakdown' => []],
            ]);
        }

        $ratings = Rating::with([
            'booking.serviceRequest.shopLocation.shop',
            'rater',
        ])
            ->where('ratee_id', $provider->user_id)
            ->where('rater_type', 'shop')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Build star breakdown (1-5)
        $breakdown = Rating::where('ratee_id', $provider->user_id)
            ->where('rater_type', 'shop')
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $fullBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $fullBreakdown[$i] = $breakdown[$i] ?? 0;
        }

        return Inertia::render('provider/Ratings', [
            'needsProfile' => false,
            'ratings' => $ratings,
            'stats' => [
                'average' => $provider->average_rating,
                'total' => $provider->total_ratings,
                'breakdown' => $fullBreakdown,
            ],
        ]);
    }
}
