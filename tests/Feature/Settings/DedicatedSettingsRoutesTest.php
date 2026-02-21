<?php

use App\Models\Industry;
use App\Models\Provider;
use App\Models\Shop;
use App\Models\User;

test('provider settings route loads for providers', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('settings.provider'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Provider')
        ->has('provider')
    );
});

test('provider settings route forbidden for non-providers', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('settings.provider'));

    $response->assertForbidden();
});

test('provider settings route shows empty state when no provider profile exists', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    $response = $this
        ->actingAs($user)
        ->get(route('settings.provider'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Provider')
        ->where('provider', null)
    );
});

test('shop settings route loads for shop owners', function () {
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
        ->get(route('settings.shop'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Shop')
        ->has('shop')
        ->has('shopLocations')
    );
});

test('shop settings route forbidden for non-shop owners', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('settings.shop'));

    $response->assertForbidden();
});

test('admins can access both provider and shop settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $providerResponse = $this
        ->actingAs($user)
        ->get(route('settings.provider'));

    $providerResponse->assertOk();
    $providerResponse->assertInertia(fn ($page) => $page
        ->component('settings/Provider')
    );

    $shopResponse = $this
        ->actingAs($user)
        ->get(route('settings.shop'));

    $shopResponse->assertOk();
    $shopResponse->assertInertia(fn ($page) => $page
        ->component('settings/Shop')
    );
});
