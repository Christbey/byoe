<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\IndustrySkill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the provider onboarding walkthrough.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $provider = $request->user()->provider;

        // If provider profile already exists, redirect to settings
        if ($provider) {
            return redirect()->route('settings.provider')
                ->with('info', 'Your provider profile is already set up.');
        }

        $availableSkills = IndustrySkill::orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('provider/Onboarding', [
            'availableSkills' => $availableSkills,
        ]);
    }
}
