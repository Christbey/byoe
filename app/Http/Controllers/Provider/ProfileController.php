<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\IndustrySkill;
use App\Models\Provider;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the provider's profile (read-only view).
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return redirect()->route('provider.profile.edit')
                ->with('info', 'Please complete your provider profile to get started.');
        }

        // Availability data
        $defaultSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn ($day) => [$day => ['available' => false, 'start' => '08:00', 'end' => '17:00']])
            ->toArray();

        // Ratings — last 5 reviews + star breakdown
        $recentRatings = Rating::with(['booking.serviceRequest.shopLocation.shop', 'rater'])
            ->where('ratee_id', $provider->user_id)
            ->whereHas('rater', fn ($q) => $q->whereHas('shop'))
            ->latest()
            ->limit(5)
            ->get();

        $totalRatings = $provider->total_ratings ?? 0;
        $starBreakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = Rating::where('ratee_id', $provider->user_id)->where('rating', $star)->count();
            $starBreakdown[$star] = [
                'count' => $count,
                'percentage' => $totalRatings > 0 ? round(($count / $totalRatings) * 100) : 0,
            ];
        }

        return Inertia::render('provider/ShowProfile', [
            'provider' => $provider,
            'canReceivePayouts' => $provider->canReceivePayouts(),
            'isStripeOnboarded' => $provider->stripeAccount?->isFullyOnboarded() ?? false,
            'completeness' => $provider->profileCompleteness(),
            // Availability
            'schedule' => $provider->availability_schedule ?? $defaultSchedule,
            'blackoutDates' => $provider->blackout_dates ?? [],
            'minNoticeHours' => $provider->min_notice_hours ?? 24,
            // Ratings
            'recentRatings' => $recentRatings,
            'starBreakdown' => $starBreakdown,
        ]);
    }

    /**
     * Show the edit profile form.
     */
    public function edit(Request $request): Response
    {
        $provider = $request->user()->provider;

        $availableSkills = IndustrySkill::orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('provider/EditProfile', [
            'provider' => $provider,
            'availableSkills' => $availableSkills,
        ]);
    }

    /**
     * Update the provider's profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            $validator = Validator::make($request->all(), [
                'bio' => ['nullable', 'string', 'max:1000'],
                'skills' => ['nullable', 'array'],
                'years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();

            Provider::create([
                'user_id' => $request->user()->id,
                'bio' => $validated['bio'] ?? null,
                'skills' => $validated['skills'] ?? [],
                'years_experience' => $validated['years_experience'] ?? 0,
                'is_active' => true,
            ]);

            return redirect()->route('provider.profile')
                ->with('success', 'Provider profile created successfully!');
        }

        $validator = Validator::make($request->all(), [
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'array'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'service_area_max_miles' => ['nullable', 'integer', 'min:1', 'max:500'],
            'preferred_zip_codes' => ['nullable', 'array'],
            'preferred_zip_codes.*' => ['string', 'regex:/^\d{5}(-\d{4})?$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $provider->update($validated);

        return redirect()->route('provider.profile')
            ->with('success', 'Profile updated successfully!');
    }
}
