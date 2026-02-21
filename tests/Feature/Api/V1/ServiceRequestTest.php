<?php

use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('shop owner can create service request via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    Sanctum::actingAs($user, ['shop:manage']);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Barista needed',
        'description' => 'Morning shift coverage',
        'service_date' => now()->addDays(2)->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '16:00',
        'skills_required' => ['espresso', 'latte_art'],
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'status',
                'price',
                'shop_location' => ['id', 'address_line_1'],
            ],
        ]);

    expect($response->json('data.title'))->toBe('Barista needed')
        ->and($response->json('data.status'))->toBe('pending_payment');
});

test('API uses same business logic as web controllers', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Test',
        'description' => 'Test',
        'service_date' => now()->addDays(2)->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '12:00', // 4 hours
        'skills_required' => [],
    ]);

    $price = $response->json('data.price');
    $expectedPrice = config('marketplace.hourly_rate') * 4;

    // Verifies CreateServiceRequestAction calculates same price
    expect((float) $price)->toBe((float) $expectedPrice);
});

test('shop owner can list their service requests', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    ServiceRequest::factory()->count(3)->create(['shop_location_id' => $location->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/service-requests');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'status', 'price'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(3);
});

test('provider can see open service requests', function () {
    $provider = User::factory()->create();
    $provider->assignRole('provider');

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    ServiceRequest::factory()->create(['shop_location_id' => $location->id, 'status' => 'open']);
    ServiceRequest::factory()->create(['shop_location_id' => $location->id, 'status' => 'filled']);

    Sanctum::actingAs($provider, ['*']);

    $response = $this->getJson('/api/v1/service-requests');

    $response->assertOk();

    // Provider should see only open requests
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
    expect(collect($data)->every(fn ($item) => $item['status'] === 'open'))->toBeTrue();
});

test('shop owner can view their service request', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson("/api/v1/service-requests/{$serviceRequest->id}");

    $response->assertOk();

    expect($response->json('data.id'))->toBe($serviceRequest->id)
        ->and($response->json('data.title'))->toBe($serviceRequest->title);
});

test('shop owner cannot create service request for another shop location', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $user->id]); // Create user's shop

    $otherShop = Shop::factory()->create();
    $otherLocation = ShopLocation::factory()->create(['shop_id' => $otherShop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => $otherLocation->id,
        'title' => 'Test',
        'description' => 'Test',
        'service_date' => now()->addDays(2)->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '12:00',
        'skills_required' => [],
    ]);

    $response->assertForbidden();
});

test('shop owner can cancel their service request', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->deleteJson("/api/v1/service-requests/{$serviceRequest->id}");

    $response->assertOk();

    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('cancelled');
});

test('unauthenticated user cannot create service request', function () {
    $shop = Shop::factory()->create();
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => $location->id,
        'title' => 'Test',
        'description' => 'Test',
        'service_date' => now()->addDays(2)->format('Y-m-d'),
        'start_time' => '08:00',
        'end_time' => '12:00',
        'skills_required' => [],
    ]);

    $response->assertUnauthorized();
});

test('provider cannot create service request', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/service-requests', [
        'shop_location_id' => 999, // Non-existent location
        'title' => 'Test',
        'description' => 'Test',
        'service_date' => now()->addDays(2)->format('Y-m-d'),
        'start_time' => now()->addDays(2)->format('Y-m-d H:i'),
        'end_time' => now()->addDays(2)->addHours(4)->format('Y-m-d H:i'),
        'skills_required' => [],
        'price' => 100,
    ]);

    // Provider role will be denied at authorization level
    $response->assertForbidden();
});
