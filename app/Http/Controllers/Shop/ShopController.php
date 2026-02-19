<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Models\Industry;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    /**
     * Show the shop profile with tabs for profile details and locations.
     */
    public function show(Request $request): Response
    {
        $shop = $this->resolveShopProfile($request);
        $tab = $request->query('tab', 'profile');

        $locations = $shop->id
            ? $shop->locations()->orderBy('is_primary', 'desc')->orderBy('created_at', 'asc')->get()
            : collect();

        return Inertia::render('shop/ShowProfile', [
            'shop' => $shop,
            'tab' => $tab,
            'locations' => $locations,
        ]);
    }

    /**
     * Show the shop profile edit form.
     */
    public function edit(Request $request): Response
    {
        $shop = $this->resolveShopProfile($request);

        $industries = Industry::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('shop/EditProfile', [
            'shop' => $shop,
            'industries' => $industries,
        ]);
    }

    /**
     * Update the shop profile.
     */
    public function update(UpdateShopRequest $request): RedirectResponse
    {
        $shop = $request->user()->shop;

        $validated = $request->validated();

        if (! $shop) {
            $shop = $request->user()->shop()->create([
                ...$validated,
                'status' => 'active',
            ]);
        } else {
            $shop->update($validated);
        }

        return redirect()->route('shop.profile')
            ->with('success', 'Shop profile updated successfully!');
    }

    private function resolveShopProfile(Request $request): Shop
    {
        $shop = $request->user()->shop?->load('industry');

        if (! $shop && $request->user()->hasRole('admin')) {
            $shop = Shop::with('industry')->first();
        }

        if (! $shop) {
            $shop = new Shop([
                'name' => '',
                'description' => '',
                'phone' => '',
                'website' => '',
                'operating_hours' => [],
                'status' => 'inactive',
            ]);
        }

        return $shop;
    }
}
