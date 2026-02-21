<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration fails without terms acceptance', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'shop_owner',
    ])->assertSessionHasErrors('terms');

    $this->assertGuest();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'shop_owner',
        'terms' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('shop.onboarding', absolute: false));
});
