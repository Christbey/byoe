<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Industry;
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

        $industries = Industry::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $industrySkills = IndustrySkill::with('industry:id,name')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('industry_id')
            ->map(fn ($skills) => $skills->pluck('name')->values());

        return Inertia::render('provider/Onboarding', [
            'industries' => $industries,
            'industrySkills' => $industrySkills,
        ]);
    }
}
