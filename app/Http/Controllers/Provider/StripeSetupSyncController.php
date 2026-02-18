<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Stripe;

class StripeSetupSyncController extends Controller
{
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

        $stripeAccount = $provider->stripeAccount;

        try {
            Stripe::setApiKey(config('stripe.secret_key'));

            $account = Account::retrieve($stripeAccount->stripe_account_id);

            $updates = [
                'charges_enabled' => $account->charges_enabled ?? false,
                'payouts_enabled' => $account->payouts_enabled ?? false,
                'details_submitted' => $account->details_submitted ?? false,
            ];

            if (
                ($account->charges_enabled ?? false) &&
                ($account->payouts_enabled ?? false) &&
                ($account->details_submitted ?? false) &&
                ! $stripeAccount->onboarding_completed_at
            ) {
                $updates['onboarding_completed_at'] = now();
            }

            $stripeAccount->update($updates);

            Log::info('Stripe account synced on onboarding exit', [
                'provider_id' => $provider->id,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
            ]);

            return response()->json([
                'charges_enabled' => $account->charges_enabled ?? false,
                'payouts_enabled' => $account->payouts_enabled ?? false,
                'details_submitted' => $account->details_submitted ?? false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync Stripe account status', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to sync account status'], 500);
        }
    }
}
