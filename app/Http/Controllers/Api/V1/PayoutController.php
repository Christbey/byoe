<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PayoutResource;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends Controller
{
    /**
     * Display a listing of payouts.
     *
     * Providers see their own payouts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if (! $request->user()->hasAnyRole(['provider', 'admin'])) {
            return PayoutResource::collection(collect());
        }

        $query = Payout::query()
            ->with(['booking.serviceRequest.shopLocation.shop', 'provider.user']);

        // Providers see only their payouts
        if ($request->user()->hasRole('provider')) {
            $query->where('provider_id', $request->user()->provider?->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return PayoutResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }

    /**
     * Display the specified payout.
     */
    public function show(Request $request, Payout $payout): PayoutResource|JsonResponse
    {
        // Verify access - only the provider receiving the payout or admins can view
        if (! $request->user()->hasRole('admin') &&
            $payout->provider_id !== $request->user()->provider?->id) {
            return response()->json([
                'message' => 'Unauthorized access to this payout.',
            ], 403);
        }

        $payout->load(['booking.serviceRequest.shopLocation.shop', 'provider.user']);

        return PayoutResource::make($payout);
    }
}
