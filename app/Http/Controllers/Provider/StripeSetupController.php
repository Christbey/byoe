<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StripeSetupController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Show the Stripe Connect setup page with an embedded onboarding component.
     *
     * Creates a Connect account on first visit, then issues an Account Session
     * client_secret for the frontend to mount the embedded onboarding component.
     * The provider never leaves the app.
     */
    public function __invoke(Request $request): Response
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return Inertia::render('provider/StripeSetup', [
                'needsProfile' => true,
                'stripe_account' => null,
                'accountSessionSecret' => null,
                'stripePublishableKey' => null,
            ]);
        }

        $stripeAccount = $provider->stripeAccount;

        // Create the Connect account on first visit
        if (! $stripeAccount) {
            try {
                $stripeAccount = $this->stripeService->createConnectAccount($provider);
            } catch (\Exception $e) {
                Log::error('Failed to create Stripe Connect account', [
                    'provider_id' => $provider->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Generate a short-lived Account Session for the embedded component
        $accountSessionSecret = null;

        if ($stripeAccount) {
            try {
                $accountSessionSecret = $this->stripeService->createAccountSession($provider);
            } catch (\Exception $e) {
                Log::error('Failed to create Stripe Account Session', [
                    'provider_id' => $provider->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('provider/StripeSetup', [
            'needsProfile' => false,
            'stripe_account' => $stripeAccount,
            'accountSessionSecret' => $accountSessionSecret,
            'stripePublishableKey' => config('stripe.publishable_key'),
            'isOnboardingComplete' => $stripeAccount?->charges_enabled && $stripeAccount?->payouts_enabled,
        ]);
    }
}
