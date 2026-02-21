<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use App\Http\Resources\V1\ServiceRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get shop dashboard data.
     */
    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found for this user.',
            ], 403);
        }

        // Load shop relationships
        $shop->load(['industry', 'locations', 'primaryLocation']);

        // Get recent service requests
        $recentRequests = \App\Models\ServiceRequest::query()
            ->whereHas('shopLocation', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id);
            })
            ->with(['shopLocation', 'booking.provider.user'])
            ->latest()
            ->limit(10)
            ->get();

        // Get active bookings (pending, confirmed, in_progress)
        $activeBookings = \App\Models\Booking::query()
            ->whereHas('serviceRequest.shopLocation', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id);
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->with(['serviceRequest', 'provider.user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent completed bookings
        $completedBookings = \App\Models\Booking::query()
            ->whereHas('serviceRequest.shopLocation', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id);
            })
            ->where('status', 'completed')
            ->with(['serviceRequest', 'provider.user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get stats
        $stats = [
            'open_requests' => \App\Models\ServiceRequest::query()
                ->whereHas('shopLocation', function ($q) use ($shop) {
                    $q->where('shop_id', $shop->id);
                })
                ->where('status', 'open')
                ->count(),
            'active_bookings' => \App\Models\Booking::query()
                ->whereHas('serviceRequest.shopLocation', function ($q) use ($shop) {
                    $q->where('shop_id', $shop->id);
                })
                ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                ->count(),
            'completed_bookings' => \App\Models\Booking::query()
                ->whereHas('serviceRequest.shopLocation', function ($q) use ($shop) {
                    $q->where('shop_id', $shop->id);
                })
                ->where('status', 'completed')
                ->count(),
            'total_spent' => \App\Models\Payment::query()
                ->whereHas('booking.serviceRequest.shopLocation', function ($q) use ($shop) {
                    $q->where('shop_id', $shop->id);
                })
                ->where('status', 'succeeded')
                ->sum('amount'),
        ];

        return response()->json([
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'description' => $shop->description,
                'status' => $shop->status,
                'available_skills' => $shop->availableSkills(),
                'locations_count' => $shop->locations()->count(),
            ],
            'recent_requests' => ServiceRequestResource::collection($recentRequests),
            'active_bookings' => BookingResource::collection($activeBookings),
            'recent_completed_bookings' => BookingResource::collection($completedBookings),
            'stats' => $stats,
        ]);
    }
}
