<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Shop owners without a shop profile
        if ($user->hasRole('shop_owner') && ! $user->shop) {
            return redirect()->route('shop.onboarding')
                ->with('info', 'Please complete your shop profile to get started.');
        }

        // Providers without a provider profile
        if ($user->hasRole('provider') && ! $user->provider) {
            return redirect()->route('provider.onboarding')
                ->with('info', 'Please complete your provider profile to get started.');
        }

        // Redirect to their intended page or dashboard
        return redirect()->intended(config('fortify.home'));
    }
}
