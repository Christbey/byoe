<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $shop = $this->resolveShop($request);

        $locationIds = $shop->locations()->pluck('id');
        $filter = $request->query('filter', 'all');

        $query = Booking::with([
            'serviceRequest.shopLocation.shop',
            'provider.user',
        ])
            ->whereHas('serviceRequest', function ($q) use ($locationIds) {
                $q->whereIn('shop_location_id', $locationIds);
            });

        match ($filter) {
            'active' => $query->whereIn('status', ['pending', 'confirmed', 'in_progress']),
            'completed' => $query->where('status', 'completed'),
            'cancelled' => $query->where('status', 'cancelled'),
            default => null,
        };

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('shop/Bookings', [
            'bookings' => $bookings,
            'filter' => $filter,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): never
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): never
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load([
            'serviceRequest.shopLocation.shop',
            'provider.user',
            'payment',
            'ratings',
            'disputes.filedByUser',
            'disputes.resolvedByUser',
        ]);

        $user = $request->user();
        $hasRated = $booking->ratings()
            ->where('rater_id', $user->id)
            ->where('rater_type', 'shop')
            ->exists();

        return Inertia::render('shop/ShowBooking', [
            'booking' => $booking,
            'clientSecret' => null,
            'stripePublishableKey' => config('stripe.publishable_key'),
            'hasRated' => $hasRated,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(): never
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(): never
    {
        abort(404);
    }

    /**
     * Cancel the booking.
     */
    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        try {
            $this->bookingService->cancelBooking($booking, 'Cancelled by shop');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('shop.bookings.index')
            ->with('success', 'Booking cancelled successfully.');
    }

    /**
     * Mark booking as complete.
     */
    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('complete', $booking);

        try {
            $this->bookingService->completeBooking($booking);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->back()
            ->with('success', 'Booking marked as complete!');
    }

    /**
     * Rate the provider for a booking.
     */
    public function rate(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('rate', $booking);

        if (! $booking->isCompleted()) {
            return redirect()->back()
                ->withErrors(['booking' => 'You can only rate a completed booking.']);
        }

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $existingRating = $booking->ratings()
            ->where('rater_id', $user->id)
            ->where('rater_type', 'shop')
            ->first();

        if ($existingRating) {
            return redirect()->back()
                ->withErrors(['rating' => 'You have already rated this provider.']);
        }

        $booking->ratings()->create([
            'rater_id' => $user->id,
            'ratee_id' => $booking->provider->user_id,
            'rater_type' => 'shop',
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $provider = $booking->provider;
        $provider->updateRatingAggregates();

        return redirect()->back()
            ->with('success', 'Thank you for your rating!');
    }
}
