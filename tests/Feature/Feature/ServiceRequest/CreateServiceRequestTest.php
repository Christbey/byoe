<?php

use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('authenticated shop owner can create service request', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceDate = now()->addDays(5);
    $startTime = now()->addDays(5)->setTime(9, 0);
    $endTime = now()->addDays(5)->setTime(17, 0);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista for morning shift',
        'description' => 'Looking for experienced barista',
        'skills_required' => ['espresso', 'latte_art'],
        'service_date' => $serviceDate->toDateString(),
        'start_time' => $startTime->format('H:i'),
        'end_time' => $endTime->format('H:i'),
        'price' => 150.00,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'shop_location_id',
                'title',
                'description',
                'skills_required',
                'service_date',
                'start_time',
                'end_time',
                'price',
                'status',
                'expires_at',
            ],
        ]);

    $this->assertDatabaseHas('service_requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista for morning shift',
        'status' => 'pending_payment',
    ]);
});

test('unauthenticated user cannot create service request', function () {
    $shop = Shop::factory()->create();
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->toISOString(),
        'end_time' => now()->addDays(5)->setTime(17, 0)->toISOString(),
        'price' => 150.00,
    ]);

    $response->assertStatus(401);
});

test('non shop owner cannot create service request', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $otherUser->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->toISOString(),
        'end_time' => now()->addDays(5)->setTime(17, 0)->toISOString(),
        'price' => 150.00,
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
});

test('validation fails for missing required fields', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        // Missing title, description, service_date, times, and price
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'service_date', 'start_time', 'end_time']);
});

test('validation fails for invalid service date in past', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->subDays(1)->toDateString(),
        'start_time' => now()->subDays(1)->setTime(9, 0)->toISOString(),
        'end_time' => now()->subDays(1)->setTime(17, 0)->toISOString(),
        'price' => 150.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_date']);
});

test('validation fails for negative price', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->toISOString(),
        'end_time' => now()->addDays(5)->setTime(17, 0)->toISOString(),
        'price' => -50.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

test('validation fails for end time before start time', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(17, 0)->toISOString(),
        'end_time' => now()->addDays(5)->setTime(9, 0)->toISOString(),
        'price' => 150.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['end_time']);
});

test('expires at is set correctly based on config', function () {
    Config::set('marketplace.service_request.expiration_hours', 72);

    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->format('H:i'),
        'end_time' => now()->addDays(5)->setTime(17, 0)->format('H:i'),
        'price' => 150.00,
    ]);

    $response->assertStatus(201);

    $serviceRequest = \App\Models\ServiceRequest::latest()->first();

    // Verify expires_at is set (expiration window defined in config)
    expect($serviceRequest->expires_at)
        ->not->toBeNull()
        ->and($serviceRequest->expires_at->isFuture())->toBeTrue();
});

test('validation fails for non-existent shop location', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => 99999,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->format('H:i'),
        'end_time' => now()->addDays(5)->setTime(17, 0)->format('H:i'),
        'price' => 150.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_location_id']);
});

test('service request includes skills required array', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user)->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Need barista',
        'description' => 'Looking for barista',
        'skills_required' => ['espresso', 'latte_art', 'customer_service'],
        'service_date' => now()->addDays(5)->toDateString(),
        'start_time' => now()->addDays(5)->setTime(9, 0)->format('H:i'),
        'end_time' => now()->addDays(5)->setTime(17, 0)->format('H:i'),
        'price' => 150.00,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('service_requests', [
        'shop_location_id' => $location->id,
    ]);

    $serviceRequest = \App\Models\ServiceRequest::latest()->first();
    expect($serviceRequest->skills_required)
        ->toBeArray()
        ->toContain('espresso', 'latte_art', 'customer_service');
});
