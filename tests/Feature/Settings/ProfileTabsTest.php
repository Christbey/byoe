<?php

use App\Models\Industry;
use App\Models\Provider;
use App\Models\Shop;
use App\Models\User;

test('personal profile page loads for all users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->has('mustVerifyEmail')
        ->has('status')
    );
});

// Provider and shop settings moved to separate pages - see DedicatedSettingsRoutesTest

test('provider profile route redirects to settings provider', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get('/provider/profile');

    $response->assertRedirect(route('settings.provider'));
});

test('shop profile route redirects to settings shop', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Shop::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/shop/profile');

    $response->assertRedirect(route('settings.shop'));
});

// Old tab-based tests removed - provider and shop now have separate pages
// See DedicatedSettingsRoutesTest for provider and shop settings tests
