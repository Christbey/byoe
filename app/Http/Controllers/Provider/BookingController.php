<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
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

        if (! $provider) {
            return Inertia::render('provider/MyBookings', [
                'needsProfile' => true,
                'bookings' => [],
                'filter' => 'all',
            ]);
        }

        $filter = $request->query('filter', 'all');

        $query = Booking::with([
            'serviceRequest.shopLocation.shop',
            'payment',
        ])
            ->where('provider_id', $provider->id)
            ->when($filter !== 'all', function ($query) use ($filter) {
                $query->where('status', $filter);
            })
            ->orderBy('created_at', 'desc');

        $bookings = $query->paginate(15);

        return Inertia::render('provider/MyBookings', [
            'needsProfile' => false,
            'bookings' => $bookings,
            'filter' => $filter,
        ]);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, Booking $booking): Response
    {
        $provider = $request->user()->provider;

        // Ensure the booking belongs to this provider
        if ($booking->provider_id !== $provider?->id) {
            abort(403, 'You do not have permission to view this booking.');
        }

        $booking->load([
            'serviceRequest.shopLocation.shop',
            'payment',
            'payout',
            'ratings',
        ]);

        $user = $request->user();
        $hasRated = $booking->ratings()
            ->where('rater_id', $user->id)
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
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->back()
            ->with('success', 'Thank you for your rating!');
    }
}
