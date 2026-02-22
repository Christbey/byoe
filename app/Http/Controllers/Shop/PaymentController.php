<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Display payments history and payment method management in a tabbed view.
     */
    public function index(Request $request): InertiaResponse
    {
        $tab = $request->query('tab', 'history');

        $shop = $this->resolveShop($request);

        // Get filter from query params (default: 'all')
        $filter = $request->query('filter', 'all');

        // Get shop location IDs
        $locationIds = $shop->locations()->pluck('id');

        // Build query for payments (captured/completed)
        $query = Payment::with([
            'booking.serviceRequest',
        ])
            ->whereHas('booking.serviceRequest', function ($query) use ($locationIds) {
                $query->whereIn('shop_location_id', $locationIds);
            })
            ->when($filter !== 'all', function ($query) use ($filter) {
                $query->where('status', $filter);
            })
            ->orderBy('created_at', 'desc');

        // Paginate results (15 per page)
        $payments = $query->paginate(15);

        // Pending authorizations: open requests with a Stripe hold but no booking yet
        $pendingAuthorizations = ServiceRequest::with(['shopLocation'])
            ->whereIn('shop_location_id', $locationIds)
            ->where('status', 'open')
            ->whereNotNull('stripe_payment_intent_id')
            ->whereDoesntHave('booking')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'price', 'stripe_payment_intent_id', 'created_at']);

        // Payment method data — only fetched when the method tab is active
        $clientSecret = null;
        $savedCard = null;
        $stripePublishableKey = null;

        if ($tab === 'method') {
            $stripePublishableKey = config('stripe.publishable_key');

            if ($shop->stripe_payment_method_id) {
                $savedCard = $this->stripeService->getPaymentMethodDetails($shop->stripe_payment_method_id);

                // Clear stale reference if Stripe no longer has the payment method
                if ($savedCard === null) {
                    $shop->update(['stripe_payment_method_id' => null]);
                }
            }

            try {
                $clientSecret = $this->stripeService->createShopSetupIntent($shop);
            } catch (\Exception $e) {
                Log::error('Failed to create SetupIntent for shop', [
                    'shop_id' => $shop->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('shop/Payments', [
            'tab' => $tab,
            'payments' => $payments,
            'pendingAuthorizations' => $pendingAuthorizations,
            'filter' => $filter,
            'clientSecret' => $clientSecret,
            'savedCard' => $savedCard,
            'stripePublishableKey' => $stripePublishableKey,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Payments are not created directly by shops
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Payments are not created directly by shops
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Not implemented yet
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Payments cannot be edited
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Payments cannot be edited
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Payments cannot be deleted
        abort(404);
    }

    /**
     * Download receipt for a payment.
     */
    public function receipt(Request $request, Payment $payment): Response
    {
        // Load relationships
        $payment->load(['booking.serviceRequest.shopLocation.shop', 'booking.provider.user']);

        // Verify the user has permission
        $user = $request->user();
        if (! $user->hasRole('admin') && $payment->booking->serviceRequest->shopLocation->shop->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this receipt.');
        }

        // TODO: Generate PDF receipt
        // For now, return a simple text receipt

        $receipt = "PAYMENT RECEIPT\n\n";
        $receipt .= "Receipt #: {$payment->id}\n";
        $receipt .= "Date: {$payment->created_at->format('F d, Y')}\n\n";
        $receipt .= "Service: {$payment->booking->serviceRequest->title}\n";
        $receipt .= 'Amount: $'.number_format($payment->amount / 100, 2)."\n";
        $receipt .= "Status: {$payment->status}\n\n";
        $receipt .= "Thank you for your business!\n";

        return response($receipt, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="receipt-'.$payment->id.'.txt"');
    }
}
