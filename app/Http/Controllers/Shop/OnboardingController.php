<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the shop onboarding walkthrough.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $shop = $request->user()->shop;

        // If shop profile already exists, redirect to settings
        if ($shop) {
            return redirect()->route('settings.shop')
                ->with('info', 'Your shop profile is already set up.');
        }

        $industries = Industry::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return Inertia::render('shop/OnboardingWizard', [
            'industries' => $industries,
        ]);
    }
}
