<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentMethodSaveController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Save a confirmed payment method for the shop.
     *
     * Called after stripe.confirmSetup() resolves on the frontend. The payment_method_id
     * is the Stripe PaymentMethod ID that was confirmed during SetupIntent completion.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method_id' => ['required', 'string', 'starts_with:pm_'],
        ]);

        $shop = $this->resolveShop($request);

        try {
            $this->stripeService->saveShopPaymentMethod($shop, $request->payment_method_id);

            return redirect()->route('shop.payment')
                ->with('success', 'Payment method saved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to save payment method for shop', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('shop.payment')
                ->withErrors(['payment' => 'Failed to save payment method. Please try again.']);
        }
    }
}
