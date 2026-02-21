<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    /**
     * Get shop's bookings.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return BookingResource::collection(collect());
        }

        $query = \App\Models\Booking::query()
            ->whereHas('serviceRequest.shopLocation', function ($q) use ($shop) {
                $q->where('shop_id', $shop->id);
            })
            ->with(['serviceRequest.shopLocation', 'provider.user', 'payment', 'payout', 'ratings']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active/past
        if ($request->filter === 'active') {
            $query->whereIn('status', ['pending', 'confirmed', 'in_progress']);
        } elseif ($request->filter === 'past') {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        return BookingResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }
}
