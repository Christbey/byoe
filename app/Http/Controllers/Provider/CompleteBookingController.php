<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompleteBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function __invoke(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('complete', $booking);

        try {
            $this->bookingService->completeBooking($booking);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.bookings.show', $booking)
            ->with('success', 'Booking marked as complete. Your payout will be processed shortly.');
    }
}
