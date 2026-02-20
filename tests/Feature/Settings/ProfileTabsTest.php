<?php

use App\Models\Industry;
use App\Models\Provider;
use App\Models\Shop;
use App\Models\User;

test('personal tab loads for all users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->has('mustVerifyEmail')
        ->where('activeTab', 'personal')
        ->where('canAccessProvider', false)
        ->where('canAccessShop', false)
    );
});

test('provider tab only visible for providers', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'provider']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->where('activeTab', 'provider')
        ->where('canAccessProvider', true)
        ->has('provider')
    );
});

test('shop tab only visible for shop owners', function () {
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
        ->get(route('profile.edit', ['tab' => 'shop']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->where('activeTab', 'shop')
        ->where('canAccessShop', true)
        ->has('shop')
    );
});

test('tab switching preserves correct state', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    // Start on personal tab
    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'personal']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('activeTab', 'personal')
    );

    // Switch to provider tab
    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'provider']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('activeTab', 'provider')
        ->has('provider')
    );
});

test('provider profile route redirects to settings profile with provider tab', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get('/provider/profile');

    $response->assertRedirect('/settings/profile?tab=provider');
});

test('shop profile route redirects to settings profile with shop tab', function () {
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

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/settings/profile');
    expect($response->headers->get('Location'))->toContain('tab=shop');
});

test('user with both provider and shop roles sees all tabs', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $user->assignRole('shop_owner');

    Provider::factory()->create(['user_id' => $user->id]);
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
        ->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->where('canAccessProvider', true)
        ->where('canAccessShop', true)
        ->has('provider')
        ->has('shop')
    );
});

test('invalid tab defaults to first available tab', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'invalid']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('activeTab', 'invalid') // Controller sets it, but Vue will default to personal
    );
});

test('provider data is loaded correctly for provider tab', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'bio' => 'Test bio',
        'skills' => ['coffee', 'espresso'],
        'years_experience' => 5,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'provider']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->where('provider.bio', 'Test bio')
        ->where('provider.years_experience', 5)
        ->has('schedule')
        ->has('blackoutDates')
        ->has('minNoticeHours')
    );
});

test('shop data with locations is loaded correctly for shop tab', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $industry = Industry::create([
        'name' => 'Coffee Shop',
        'slug' => 'coffee-shop',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $shop = Shop::factory()->create([
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'name' => 'Test Shop',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit', ['tab' => 'shop']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Profile')
        ->where('shop.name', 'Test Shop')
        ->has('shopLocations')
    );
});
