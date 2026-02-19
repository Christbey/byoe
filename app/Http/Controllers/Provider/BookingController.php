<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\RateBookingRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Display a listing of the provider's bookings.
     */
    public function index(Request $request): Response
    {
        $provider = $request->user()->provider;

        $filter = $request->query('filter', 'all');

        $bookings = Booking::with(['serviceRequest.shopLocation.shop', 'payment'])
            ->where('provider_id', $provider->id)
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('provider/MyBookings', [
            'bookings' => $bookings,
            'filter' => $filter,
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['serviceRequest.shopLocation.shop', 'payment', 'payout', 'ratings']);

        $hasRated = $booking->ratings()
            ->where('rater_id', $request->user()->id)
            ->where('rater_type', 'provider')
            ->exists();

        return Inertia::render('provider/ShowBooking', [
            'booking' => $booking,
            'hasRated' => $hasRated,
        ]);
    }

    /**
     * Rate the shop for a completed booking.
     */
    public function rate(RateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('rate', $booking);

        if (! $booking->isCompleted()) {
            return redirect()->back()
                ->withErrors(['booking' => 'You can only rate a completed booking.']);
        }

        $user = $request->user();

        $existingRating = $booking->ratings()
            ->where('rater_id', $user->id)
            ->where('rater_type', 'provider')
            ->first();

        if ($existingRating) {
            return redirect()->back()
                ->withErrors(['rating' => 'You have already rated this booking.']);
        }

        $shop = $booking->serviceRequest->shopLocation->shop;

        $booking->ratings()->create([
            'rater_id' => $user->id,
            'ratee_id' => $shop->user_id,
            'rater_type' => 'provider',
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return redirect()->back()->with('success', 'Thank you for your rating!');
    }
}
