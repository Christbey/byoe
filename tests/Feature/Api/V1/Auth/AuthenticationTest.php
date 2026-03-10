<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('user can register via API', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'shop_owner',
        'device_name' => 'api-test',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'roles'],
            'token',
            'expires_at',
        ]);

    expect($response->json('user.name'))->toBe('Test User')
        ->and($response->json('user.email'))->toBe('test@example.com')
        ->and($response->json('token'))->toContain('|');

    $tokenPrefix = (string) config('sanctum.token_prefix', '');
    if ($tokenPrefix !== '') {
        expect($response->json('token'))->toContain($tokenPrefix);
    }

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('user can register as provider via API', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Provider User',
        'email' => 'provider@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'provider',
    ]);

    $response->assertCreated();

    $user = User::where('email', 'provider@example.com')->first();
    expect($user->hasRole('provider'))->toBeTrue();
});

test('registration requires valid email', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'shop_owner',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration requires password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
        'role' => 'shop_owner',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('registration requires valid role', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'invalid_role',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('user can login via API', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('shop_owner');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
            'expires_at',
        ]);

    expect($response->json('user.id'))->toBe($user->id)
        ->and($response->json('token'))->toBeString();
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('token has role-specific abilities for shop owner', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $user->tokens()->first();
    expect($token->abilities)->toContain('shop:manage', 'bookings:view', 'payments:create');
});

test('token has role-specific abilities for provider', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $user->tokens()->first();
    expect($token->abilities)->toContain('provider:accept', 'bookings:manage');
});

test('token has all abilities for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $user->tokens()->first();
    expect($token->abilities)->toContain('*');
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Successfully logged out']);

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated user cannot access me endpoint', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});

test('registration is rate limited', function () {
    // Make 11 requests (limit is 10 per minute)
    for ($i = 0; $i < 11; $i++) {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User '.$i,
            'email' => "test{$i}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'shop_owner',
        ]);

        if ($i < 10) {
            $response->assertStatus(201);
        } else {
            $response->assertStatus(429); // Too Many Requests
        }
    }
});
