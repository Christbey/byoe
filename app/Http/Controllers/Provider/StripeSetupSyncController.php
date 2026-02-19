<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeSetupSyncController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Sync the provider's Stripe account status from the Stripe API.
     *
     * Called by the frontend after the embedded onboarding component's onExit
     * fires, so the database is up to date before the page reloads — without
     * depending on the account.updated webhook arriving in time.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;

        if (! $provider || ! $provider->stripeAccount) {
            return response()->json(['error' => 'No Stripe account found'], 422);
        }

        try {
            $status = $this->stripeService->syncStripeAccount($provider->stripeAccount);

            Log::info('Stripe account synced on onboarding exit', [
                'provider_id' => $provider->id,
                ...$status,
            ]);

            return response()->json($status);
        } catch (\Exception $e) {
            Log::error('Failed to sync Stripe account status', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to sync account status'], 500);
        }
    }
}
