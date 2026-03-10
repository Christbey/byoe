<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Rating;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the provider settings page.
     */
    public function provider(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasRole('provider')) {
            abort(403);
        }

        $data = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ];

        $provider = $user->provider;

        if ($provider) {
            $provider->refreshTrustMetrics();

            $defaultSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                ->mapWithKeys(fn ($day) => [$day => ['available' => false, 'start' => '08:00', 'end' => '17:00']])
                ->toArray();

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

            $data['provider'] = $provider;
            $data['canReceivePayouts'] = $provider->canReceivePayouts();
            $data['isStripeOnboarded'] = $provider->stripeAccount?->isFullyOnboarded() ?? false;
            $data['completeness'] = $provider->profileCompleteness();
            $data['schedule'] = $provider->availability_schedule ?? $defaultSchedule;
            $data['blackoutDates'] = $provider->blackout_dates ?? [];
            $data['minNoticeHours'] = $provider->min_notice_hours ?? 24;
            $data['recentRatings'] = $recentRatings;
            $data['starBreakdown'] = $starBreakdown;
        } else {
            $data['provider'] = null;
        }

        return Inertia::render('settings/Provider', $data);
    }

    /**
     * Show the shop settings page.
     */
    public function shop(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasRole('shop_owner') && ! $user->hasRole('shop_manager') && ! $user->hasRole('admin')) {
            abort(403);
        }

        $subtab = $request->query('subtab', 'profile');

        $data = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'subtab' => $subtab,
        ];

        $shop = $user->shop;
        if ($shop) {
            $shop->load('industry');
        }
        $data['shop'] = $shop ?? (object) ['id' => null];
        $data['shopLocations'] = $shop?->locations ?? collect();

        return Inertia::render('settings/Shop', $data);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
