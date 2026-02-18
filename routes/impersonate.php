<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Impersonation Routes
|--------------------------------------------------------------------------
|
| Routes for admins to impersonate other users.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/impersonate/user/{user}', function ($userId) {
        $user = \App\Models\User::findOrFail($userId);

        if (! auth()->user()->canImpersonate()) {
            abort(403, 'You are not authorized to impersonate users.');
        }

        if (! $user->canBeImpersonated()) {
            abort(403, 'This user cannot be impersonated.');
        }

        auth()->user()->impersonate($user);

        // Redirect based on user's role
        if ($user->hasRole('shop_owner') || $user->hasRole('shop_manager')) {
            return redirect()->route('shop.dashboard');
        } elseif ($user->hasRole('provider')) {
            return redirect()->route('provider.dashboard');
        }

        return redirect()->route('dashboard');
    })->name('impersonate');

    Route::get('/impersonate/leave', function () {
        if (auth()->user()->isImpersonated()) {
            auth()->user()->leaveImpersonation();
        }

        return redirect()->route('admin.users');
    })->name('impersonate.leave');
});
