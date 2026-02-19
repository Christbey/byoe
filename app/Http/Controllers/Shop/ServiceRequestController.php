<?php

namespace App\Http\Controllers\Shop;

use App\Actions\CreateServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected CreateServiceRequestAction $createServiceRequestAction,
        protected StripeService $stripeService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $shop = $request->user()->shop;

        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::first();
        }

        $tab = $request->query('tab', 'requests');
        $filter = $request->query('filter', 'all');

        if (! $shop) {
            return Inertia::render('shop/ServiceRequests', [
                'tab' => $tab,
                'filter' => $filter,
                'requests' => null,
                'bookings' => null,
            ]);
        }

        if ($tab === 'bookings') {
            $locationIds = $shop->locations()->pluck('id');

            $query = Booking::with(['serviceRequest.shopLocation', 'provider.user'])
                ->whereHas('serviceRequest', fn ($q) => $q->whereIn('shop_location_id', $locationIds));

            match ($filter) {
                'active' => $query->whereIn('status', ['pending', 'confirmed', 'in_progress']),
                'completed' => $query->where('status', 'completed'),
                'cancelled' => $query->where('status', 'cancelled'),
                default => null,
            };

            return Inertia::render('shop/ServiceRequests', [
                'tab' => 'bookings',
                'filter' => $filter,
                'requests' => null,
                'bookings' => $query->orderBy('created_at', 'desc')->paginate(15),
            ]);
        }

        $query = ServiceRequest::query()
            ->with(['shopLocation', 'booking'])
            ->whereHas('shopLocation', fn ($q) => $q->where('shop_id', $shop->id))
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->orderBy('created_at', 'desc');

        return Inertia::render('shop/ServiceRequests', [
            'tab' => 'requests',
            'filter' => $filter,
            'requests' => $query->paginate(15),
            'bookings' => null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        // Get the authenticated user's shop
        $shop = $request->user()->shop;

        // If user doesn't have a shop yet (admin viewing), use the first available shop
        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::with('locations')->first();
        }

        // Get shop locations
        $locations = $shop ? $shop->locations : collect();

        // Load industry skills and templates for this shop
        $skills = [];
        $templates = [];

        if ($shop) {
            $shop->load('industry.skills', 'industry.templates');

            if ($shop->industry) {
                $skills = $shop->industry->skills->pluck('name')->toArray();
                $templates = $shop->industry->templates->toArray();
            }

            // Merge shop's custom skills (deduplicated)
            if (! empty($shop->custom_skills)) {
                $skills = array_values(array_unique(array_merge($skills, $shop->custom_skills)));
            }
        }

        return Inertia::render('shop/CreateServiceRequest', [
            'locations' => $locations,
            'skills' => $skills,
            'templates' => $templates,
            'hourlyRate' => config('marketplace.hourly_rate'),
            'platformFeePercentage' => config('marketplace.platform_fee_percentage'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'shop_location_id' => ['required', 'exists:shop_locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'skills_required' => ['nullable', 'array'],
            'service_date' => ['required', 'date', 'after:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Find the shop location
        $shopLocation = ShopLocation::findOrFail($validated['shop_location_id']);

        // Verify the shop belongs to the authenticated user (or user is admin)
        $user = $request->user();
        if (! $user->hasRole('admin') && $shopLocation->shop->user_id !== $user->id) {
            return redirect()->back()
                ->withErrors(['shop_location_id' => 'You do not have permission to create requests for this location.'])
                ->withInput();
        }

        // Reject same start and end time
        if ($validated['start_time'] === $validated['end_time']) {
            return redirect()->back()
                ->withErrors(['end_time' => 'Start and end time cannot be the same.'])
                ->withInput();
        }

        // Calculate price from flat hourly rate × shift duration
        // Handle overnight shifts: if end time is earlier than or equal to start time,
        // the shift ends on the following day.
        $startCarbon = \Carbon\Carbon::parse($validated['service_date'].' '.$validated['start_time']);
        $endCarbon = \Carbon\Carbon::parse($validated['service_date'].' '.$validated['end_time']);

        if ($endCarbon <= $startCarbon) {
            $endCarbon->addDay();
        }

        $hours = $startCarbon->diffInMinutes($endCarbon) / 60;
        $validated['price'] = round(config('marketplace.hourly_rate') * $hours, 2);

        // Combine date and time fields (end_time may be next day for overnight shifts)
        $validated['start_time'] = $startCarbon->toDateTimeString();
        $validated['end_time'] = $endCarbon->toDateTimeString();

        // Set expiration (72 hours from now by default)
        $validated['expires_at'] = now()->addHours(config('marketplace.service_request_expiration_hours', 72));

        // Create the service request (starts as pending_payment — not visible to providers yet)
        $serviceRequest = ($this->createServiceRequestAction)($shopLocation, $validated);

        // Create a Stripe payment authorization (hold on card)
        try {
            $this->stripeService->createServiceRequestAuthorization($serviceRequest);
        } catch (\Exception $e) {
            // If authorization fails, delete the service request and return error
            $serviceRequest->delete();
            Log::error('Failed to create service request authorization', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['payment' => 'Unable to authorize payment. Please check your payment method and try again.'])
                ->withInput();
        }

        // If the shop had a saved card, the PaymentIntent was confirmed off-session
        // and is already in requires_capture — open the request immediately.
        $serviceRequest->refresh();
        $shop = $request->user()->shop;

        if ($shop?->stripe_customer_id && $shop?->stripe_payment_method_id) {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
            $pi = \Stripe\PaymentIntent::retrieve($serviceRequest->stripe_payment_intent_id);

            if ($pi->status === 'requires_capture') {
                $serviceRequest->update(['status' => 'open']);

                return redirect()->route('shop.service-requests.show', $serviceRequest)
                    ->with('success', 'Your service request is now live! Providers can now see and accept it.');
            }
        }

        return redirect()->route('shop.service-requests.show', $serviceRequest)
            ->with('info', 'Almost done! Authorize the payment below to make your request live.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        // Load relationships
        $serviceRequest->load(['shopLocation.shop', 'booking.provider.user']);

        // Verify the user has permission to view this request
        $user = $request->user();
        if (! $user->hasRole('admin') && $serviceRequest->shopLocation->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this service request.');
        }

        $clientSecret = $serviceRequest->isPendingPayment()
            ? $serviceRequest->stripe_client_secret
            : null;

        return Inertia::render('shop/ShowServiceRequest', [
            'serviceRequest' => $serviceRequest,
            'clientSecret' => $clientSecret,
            'stripePublishableKey' => $clientSecret ? config('stripe.publishable_key') : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Not implemented - service requests cannot be edited once created
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Not implemented - service requests cannot be edited once created
        abort(404);
    }

    /**
     * Confirm the Stripe payment authorization, making the service request live.
     *
     * Called by the frontend after stripe.confirmPayment() resolves successfully.
     * Verifies the PaymentIntent is in requires_capture state before opening the request.
     */
    public function confirmPayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole('admin') && $serviceRequest->shopLocation->shop->user_id !== $user->id) {
            abort(403);
        }

        if (! $serviceRequest->isPendingPayment()) {
            return redirect()->back()
                ->withErrors(['payment' => 'Request is not awaiting payment.']);
        }

        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
            $pi = \Stripe\PaymentIntent::retrieve($serviceRequest->stripe_payment_intent_id);

            if ($pi->status !== 'requires_capture') {
                return redirect()->back()
                    ->withErrors(['payment' => 'Payment authorization not confirmed yet.']);
            }

            $serviceRequest->update(['status' => 'open']);

            Log::info('Service request opened after payment authorization', [
                'service_request_id' => $serviceRequest->id,
            ]);

            return redirect()->route('shop.service-requests.index')
                ->with('success', 'Your service request is now live! Providers can now see and accept it.');
        } catch (\Exception $e) {
            Log::error('Failed to confirm service request payment', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['payment' => 'Failed to verify payment. Please try again.']);
        }
    }

    /**
     * Remove the specified resource from storage (Cancel).
     */
    public function destroy(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        // Verify the user has permission to cancel this request
        $user = $request->user();
        if (! $user->hasRole('admin') && $serviceRequest->shopLocation->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to cancel this service request.');
        }

        // Allow cancelling pending_payment or open requests
        if (! in_array($serviceRequest->status, ['pending_payment', 'open'])) {
            return redirect()->back()
                ->withErrors(['request' => 'Only open service requests can be cancelled.']);
        }

        // Check if there's a booking (request has been accepted by a provider)
        $booking = $serviceRequest->booking;
        if ($booking && in_array($booking->status, ['confirmed', 'completed'])) {
            return redirect()->back()
                ->withErrors(['request' => 'Cannot cancel a request with a confirmed or completed booking. Please contact the provider.']);
        }

        // Cancel any pending Stripe authorization
        if ($serviceRequest->stripe_payment_intent_id) {
            try {
                \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
                $pi = \Stripe\PaymentIntent::retrieve($serviceRequest->stripe_payment_intent_id);
                if (in_array($pi->status, ['requires_payment_method', 'requires_confirmation', 'requires_capture'])) {
                    \Stripe\PaymentIntent::cancel($serviceRequest->stripe_payment_intent_id);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cancel Stripe PaymentIntent on service request cancellation', [
                    'service_request_id' => $serviceRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update the request status to cancelled
        $serviceRequest->update(['status' => 'cancelled']);

        // If there was a pending booking, also cancel it
        if ($booking && $booking->status === 'pending') {
            $booking->update(['status' => 'cancelled']);
        }

        return redirect()->route('shop.service-requests.index')
            ->with('success', 'Service request cancelled successfully.');
    }
}
