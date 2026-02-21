<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\EmailPreferencesUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailPreferencesController extends Controller
{
    /**
     * Show the email preferences settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/EmailPreferences', [
            'preferences' => $user->email_preferences ?? User::defaultEmailPreferences(),
        ]);
    }

    /**
     * Update the user's email preferences.
     */
    public function update(EmailPreferencesUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $currentPreferences = $user->email_preferences ?? User::defaultEmailPreferences();
        $updatedPreferences = array_merge($currentPreferences, $request->validated());

        $user->email_preferences = $updatedPreferences;
        $user->save();

        return back()->with('status', 'email-preferences-updated');
    }
}
