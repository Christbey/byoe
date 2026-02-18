<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeSetupSessionController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Return a fresh Account Session client_secret for the embedded onboarding component.
     *
     * Called by fetchClientSecret() in the frontend when the session expires (~1 hour).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;

        if (! $provider || ! $provider->stripeAccount) {
            return response()->json(['error' => 'No Stripe account found'], 422);
        }

        try {
            $clientSecret = $this->stripeService->createAccountSession($provider);

            return response()->json(['client_secret' => $clientSecret]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
