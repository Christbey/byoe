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
    ) {
    }

    public function __invoke(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->back()->with('error', 'Provider profile not found. You must have a provider profile to accept service requests.');
        }

        if (! $provider->is_active) {
            return redirect()->back()->with('error', 'Your provider profile is not active.');
        }

        if (! $provider->stripeAccount?->isFullyOnboarded()) {
            return redirect()->back()->with('error', 'You must complete your Stripe payout setup before accepting service requests.');
        }

        if (! $serviceRequest->isOpen()) {
            return redirect()->back()->with('error', 'This service request is no longer available.');
        }

        if ($serviceRequest->isExpired()) {
            return redirect()->back()->with('error', 'This service request has expired.');
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
