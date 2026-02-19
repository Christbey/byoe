<?php

namespace App\Http\Controllers\Shop;

use App\Actions\CreateServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreServiceRequestRequest;
use App\Models\Booking;
use App\Models\ServiceRequest;
use App\Models\ShopLocation;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function __construct(
        protected CreateServiceRequestAction $createServiceRequestAction,
        protected StripeService $stripeService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $shop = $this->resolveShop($request);

        $tab = $request->query('tab', 'requests');
        $filter = $request->query('filter', 'all');

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
     *
     * Intentionally uses a separate shop resolution path from resolveShop() so
     * that locations and industry skills can be eager-loaded in a single query.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $shop = $this->resolveShop($request)?->load('industry.skills', 'industry.templates');

        if (! $shop?->locations()->exists()) {
            return redirect()->route('shop.locations.create')
                ->with('info', 'Add a location for your shop before posting a service request.');
        }

        return Inertia::render('shop/CreateServiceRequest', [
            'locations' => $shop->locations,
            'skills' => $shop->availableSkills(),
            'templates' => $shop->availableTemplates(),
            'hourlyRate' => config('marketplace.hourly_rate'),
            'platformFeePercentage' => config('marketplace.platform_fee_percentage'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $shopLocation = ShopLocation::findOrFail($validated['shop_location_id']);

        // Verify the shop location belongs to the authenticated user's shop.
        if (! $request->user()->hasRole('admin') && $shopLocation->shop->user_id !== $request->user()->id) {
            return redirect()->back()
                ->withErrors(['shop_location_id' => 'You do not have permission to create requests for this location.'])
                ->withInput();
        }

        // Enforce 2-hour minimum lead time from the current server time.
        $serviceStart = Carbon::parse($validated['service_date'].' '.$validated['start_time']);
        if ($serviceStart->diffInMinutes(now(), false) > -120) {
            return redirect()->back()
                ->withErrors(['start_time' => 'The start time must be at least 2 hours from now.'])
                ->withInput();
        }

        // Reject identical start and end times.
        if ($validated['start_time'] === $validated['end_time']) {
            return redirect()->back()
                ->withErrors(['end_time' => 'Start and end time cannot be the same.'])
                ->withInput();
        }

        $serviceRequest = ($this->createServiceRequestAction)($shopLocation, $validated);

        try {
            $this->stripeService->createServiceRequestAuthorization($serviceRequest);
        } catch (\Exception $e) {
            $serviceRequest->delete();
            Log::error('Failed to create service request authorization', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withErrors(['payment' => 'Unable to authorize payment. Please check your payment method and try again.'])
                ->withInput();
        }

        // If the shop had a saved card the PaymentIntent was confirmed off-session
        // and is already in requires_capture — open the request immediately.
        $serviceRequest->refresh();

        if ($this->stripeService->paymentIntentRequiresCapture($serviceRequest)) {
            $serviceRequest->update(['status' => 'open']);

            return redirect()->route('shop.service-requests.show', $serviceRequest)
                ->with('success', 'Your service request is now live! Providers can now see and accept it.');
        }

        return redirect()->route('shop.service-requests.show', $serviceRequest)
            ->with('info', 'Almost done! Authorize the payment below to make your request live.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): Response
    {
        $serviceRequest->load(['shopLocation.shop', 'booking.provider.user']);

        $this->authorize('view', $serviceRequest);

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
     * Confirm the Stripe payment authorization, making the service request live.
     *
     * Called by the frontend after stripe.confirmPayment() resolves successfully.
     * Verifies the PaymentIntent is in requires_capture state before opening the request.
     */
    public function confirmPayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('confirmPayment', $serviceRequest);

        if (! $serviceRequest->isPendingPayment()) {
            return redirect()->back()
                ->withErrors(['payment' => 'Request is not awaiting payment.']);
        }

        if (! $this->stripeService->paymentIntentRequiresCapture($serviceRequest)) {
            return redirect()->back()
                ->withErrors(['payment' => 'Payment authorization not confirmed yet.']);
        }

        $serviceRequest->update(['status' => 'open']);

        Log::info('Service request opened after payment authorization', [
            'service_request_id' => $serviceRequest->id,
        ]);

        return redirect()->route('shop.service-requests.index')
            ->with('success', 'Your service request is now live! Providers can now see and accept it.');
    }

    /**
     * Cancel the service request.
     */
    public function destroy(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('delete', $serviceRequest);

        if (! in_array($serviceRequest->status, ['pending_payment', 'open'])) {
            return redirect()->back()
                ->withErrors(['request' => 'Only open service requests can be cancelled.']);
        }

        $booking = $serviceRequest->booking;
        if ($booking && in_array($booking->status, ['confirmed', 'completed'])) {
            return redirect()->back()
                ->withErrors(['request' => 'Cannot cancel a request with a confirmed or completed booking. Please contact the provider.']);
        }

        if ($serviceRequest->stripe_payment_intent_id) {
            $this->stripeService->cancelPaymentIntentIfCancellable($serviceRequest->stripe_payment_intent_id);
        }

        $serviceRequest->update(['status' => 'cancelled']);

        if ($booking && $booking->status === 'pending') {
            $booking->update(['status' => 'cancelled']);
        }

        return redirect()->route('shop.service-requests.index')
            ->with('success', 'Service request cancelled successfully.');
    }
}
