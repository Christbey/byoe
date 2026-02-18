<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    /**
     * Show the shop profile (read-only).
     */
    public function show(Request $request): Response
    {
        $shop = $this->resolveShop($request);

        return Inertia::render('shop/ShowProfile', [
            'shop' => $shop,
        ]);
    }

    /**
     * Show the shop profile edit form.
     */
    public function edit(Request $request): Response
    {
        $shop = $this->resolveShop($request);

        $industries = Industry::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('shop/EditProfile', [
            'shop' => $shop,
            'industries' => $industries,
        ]);
    }

    /**
     * Update the shop profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $shop = $request->user()->shop;

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'operating_hours' => ['nullable', 'array'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'custom_skills' => ['nullable', 'array'],
            'ein' => ['nullable', 'string', 'regex:/^\d{2}-\d{7}$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

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

    private function resolveShop(Request $request): Shop
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
