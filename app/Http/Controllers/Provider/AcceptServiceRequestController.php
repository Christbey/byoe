<?php

namespace App\Http\Controllers\Provider;

use App\Actions\AcceptServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcceptServiceRequestController extends Controller
{
    public function __construct(
        protected AcceptServiceRequestAction $acceptServiceRequestAction
    ) {}

    public function __invoke(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('accept', $serviceRequest);

        $provider = $request->user()->provider;

        if (! $provider->is_active) {
            return redirect()->back()->with('error', 'Your provider account is not currently active.');
        }

        if (! $provider->stripeAccount?->isFullyOnboarded()) {
            return redirect()->back()->with('error', 'Please complete your Stripe payout setup before accepting requests.');
        }

        try {
            $booking = ($this->acceptServiceRequestAction)($serviceRequest, $provider);

            return redirect()
                ->route('provider.bookings.show', $booking)
                ->with('success', 'Service request accepted! Your booking has been created.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to accept service request: '.$e->getMessage());
        }
    }
}
