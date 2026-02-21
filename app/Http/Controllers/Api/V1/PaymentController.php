<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     *
     * Shop owners see payments for their bookings.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if (! $request->user()->hasAnyRole(['shop_owner', 'admin'])) {
            return PaymentResource::collection(collect());
        }

        $query = Payment::query()
            ->with(['booking.serviceRequest.shopLocation.shop', 'booking.provider.user']);

        // Shop owners see only their payments
        if ($request->user()->hasRole('shop_owner')) {
            $query->whereHas('booking.serviceRequest.shopLocation.shop', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return PaymentResource::collection(
            $query->latest()->cursorPaginate(15)
        );
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, Payment $payment): PaymentResource|JsonResponse
    {
        // Load relationships
        $payment->load('booking.serviceRequest.shopLocation.shop');

        // Verify access
        $shop = $request->user()->shop;
        if (! $request->user()->hasRole('admin') &&
            (! $shop || $payment->booking->serviceRequest->shopLocation->shop_id !== $shop->id)) {
            return response()->json([
                'message' => 'Unauthorized access to this payment.',
            ], 403);
        }

        return PaymentResource::make($payment);
    }
}
