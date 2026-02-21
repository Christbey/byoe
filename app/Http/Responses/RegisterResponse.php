<?php

namespace App\Http\Responses;

use App\Mail\User\WelcomeEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Send welcome email
        Mail::to($user->email)->queue(new WelcomeEmail($user));

        // Redirect shop owners to shop onboarding
        if ($user->hasRole('shop_owner')) {
            return redirect()->route('shop.onboarding')
                ->with('success', 'Welcome! Let\'s set up your shop profile.');
        }

        // Redirect providers to provider onboarding
        if ($user->hasRole('provider')) {
            return redirect()->route('provider.onboarding')
                ->with('success', 'Welcome! Let\'s set up your provider profile.');
        }

        // Default fallback to dashboard
        return redirect()->intended(config('fortify.home'));
    }
}
