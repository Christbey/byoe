<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Show the payment method management page.
     *
     * Creates a Stripe Customer and SetupIntent on first visit so the shop can
     * save a card. On return visits, shows the saved card (brand + last4 + expiry)
     * and allows updating it.
     */
    public function __invoke(Request $request): Response
    {
        $shop = $this->resolveShop($request);

        // Fetch saved card details from Stripe if one is on file
        $savedCard = null;

        if ($shop->stripe_payment_method_id) {
            $savedCard = $this->stripeService->getPaymentMethodDetails($shop->stripe_payment_method_id);

            // Clear stale reference if Stripe no longer has the payment method
            if ($savedCard === null) {
                $shop->update(['stripe_payment_method_id' => null]);
            }
        }

        // Always create a fresh SetupIntent so the shop can add or update their card
        $clientSecret = null;

        try {
            $clientSecret = $this->stripeService->createShopSetupIntent($shop);
        } catch (\Exception $e) {
            Log::error('Failed to create SetupIntent for shop', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }

        return Inertia::render('shop/PaymentMethod', [
            'clientSecret' => $clientSecret,
            'stripePublishableKey' => config('stripe.publishable_key'),
            'savedCard' => $savedCard,
        ]);
    }
}
