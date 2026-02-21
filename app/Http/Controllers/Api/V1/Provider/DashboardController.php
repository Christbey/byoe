<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Http\Resources\V1\ServiceRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get provider dashboard data.
     */
    public function index(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return response()->json([
                'message' => 'Provider profile not found.',
            ], 403);
        }

        // Load provider relationships
        $provider->load(['stripeAccount', 'industry']);

        // Get upcoming bookings
        $upcomingBookings = $provider->upcomingBookings()
            ->with(['serviceRequest.shopLocation.shop'])
            ->limit(5)
            ->get();

        // Get recent completed bookings
        $completedBookings = $provider->completedBookings()
            ->limit(5)
            ->get();

        // Get available service requests (open, not expired)
        $availableRequests = \App\Models\ServiceRequest::query()
            ->where('status', 'open')
            ->where('expires_at', '>', now())
            ->with(['shopLocation.shop'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'bio' => $provider->bio,
                'skills' => $provider->skills,
                'average_rating' => $provider->average_rating,
                'total_ratings' => $provider->total_ratings,
                'completed_bookings' => $provider->completed_bookings,
                'is_active' => $provider->is_active,
                'can_receive_payouts' => $provider->canReceivePayouts(),
                'profile_completeness' => $provider->profileCompleteness(),
            ],
            'upcoming_bookings' => BookingResource::collection($upcomingBookings),
            'recent_completed_bookings' => BookingResource::collection($completedBookings),
            'available_requests' => ServiceRequestResource::collection($availableRequests),
            'stats' => [
                'upcoming_count' => $provider->upcomingBookings()->count(),
                'completed_count' => $provider->completed_bookings,
                'total_earnings' => $provider->payouts()->where('status', 'paid')->sum('amount'),
            ],
        ]);
    }
}
