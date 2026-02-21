<?php

use App\Models\Provider;
use App\Models\User;

test('onboarding page loads for providers without profile', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    $response = $this
        ->actingAs($user)
        ->get(route('provider.onboarding'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('provider/OnboardingWizard')
        ->has('industries')
        ->has('industrySkills')
    );
});

test('onboarding redirects to settings if provider profile exists', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('provider.onboarding'));

    $response->assertRedirect(route('settings.provider'));
    $response->assertSessionHas('info', 'Your provider profile is already set up.');
});

test('onboarding requires provider role', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('provider.onboarding'));

    $response->assertForbidden();
});

test('admins can access onboarding', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this
        ->actingAs($user)
        ->get(route('provider.onboarding'));

    $response->assertOk();
});
