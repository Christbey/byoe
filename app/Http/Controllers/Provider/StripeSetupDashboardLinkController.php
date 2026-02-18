<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeSetupDashboardLinkController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Generate a single-use Express Dashboard login link for the authenticated provider.
     *
     * Returns a JSON response containing the URL to the provider's Stripe Express
     * Dashboard where they can view their balance, payout history, and tax documents.
     * The frontend opens this URL in a new tab.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return response()->json(['error' => 'Provider profile not found.'], 404);
        }

        try {
            $url = $this->stripeService->createExpressDashboardLink($provider);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('Failed to generate Express Dashboard link', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Unable to open Stripe Dashboard. Please try again.'], 422);
        }
    }
}
