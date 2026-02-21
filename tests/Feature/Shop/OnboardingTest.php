<?php

use App\Models\Shop;
use App\Models\User;

test('onboarding page loads for shop owners without shop', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    $response = $this
        ->actingAs($user)
        ->get(route('shop.onboarding'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('shop/OnboardingWizard')
        ->has('industries')
    );
});

test('onboarding redirects to settings if shop profile exists', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('shop.onboarding'));

    $response->assertRedirect(route('settings.shop'));
    $response->assertSessionHas('info', 'Your shop profile is already set up.');
});

test('onboarding requires shop owner role', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('shop.onboarding'));

    $response->assertForbidden();
});

test('admins can access onboarding', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this
        ->actingAs($user)
        ->get(route('shop.onboarding'));

    $response->assertOk();
});
