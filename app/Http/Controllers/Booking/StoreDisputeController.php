<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreDisputeController extends Controller
{
    public function __invoke(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('view', $booking);
        $this->authorize('create', Dispute::class);

        $validated = $request->validate([
            'dispute_type' => ['required', 'in:payment,service_quality,cancellation,no_show'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        $hasActiveDispute = $booking->disputes()
            ->where('filed_by_user_id', $request->user()->id)
            ->whereIn('status', ['open', 'under_review'])
            ->exists();

        if ($hasActiveDispute) {
            return redirect()->back()->withErrors([
                'dispute' => 'You already have an active dispute open for this booking.',
            ]);
        }

        $booking->disputes()->create([
            'filed_by_user_id' => $request->user()->id,
            'dispute_type' => $validated['dispute_type'],
            'description' => $validated['description'],
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', 'Dispute submitted. Our team will review it.');
    }
}
