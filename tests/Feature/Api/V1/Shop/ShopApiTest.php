<?php

use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('shop owner can get dashboard via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    ShopLocation::factory()->create(['shop_id' => $shop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/shop/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'shop' => ['id', 'name', 'status', 'available_skills'],
            'recent_requests',
            'active_bookings',
            'recent_completed_bookings',
            'stats',
        ]);

    expect($response->json('shop.id'))->toBe($shop->id);
});

test('shop owner can create location via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/shop/locations', [
        'address_line_1' => '123 Main St',
        'city' => 'Portland',
        'state' => 'OR',
        'zip_code' => '97201',
        'is_primary' => true,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'address_line_1', 'city', 'state'],
        ]);

    $this->assertDatabaseHas('shop_locations', [
        'shop_id' => $shop->id,
        'address_line_1' => '123 Main St',
        'city' => 'Portland',
    ]);
});

test('shop owner can update location via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->patchJson("/api/v1/shop/locations/{$location->id}", [
        'address_line_1' => '456 New St',
    ]);

    $response->assertOk();

    $location->refresh();
    expect($location->address_line_1)->toBe('456 New St');
});

test('shop owner can delete location via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->deleteJson("/api/v1/shop/locations/{$location->id}");

    $response->assertOk();

    $this->assertDatabaseMissing('shop_locations', [
        'id' => $location->id,
    ]);
});

test('shop owner cannot delete location with service requests', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    \App\Models\ServiceRequest::factory()->create(['shop_location_id' => $location->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->deleteJson("/api/v1/shop/locations/{$location->id}");

    $response->assertUnprocessable()
        ->assertJson(['message' => 'Cannot delete location with existing service requests.']);
});

test('shop owner cannot access another shops location', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $user->id]);

    $otherShop = Shop::factory()->create();
    $otherLocation = ShopLocation::factory()->create(['shop_id' => $otherShop->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson("/api/v1/shop/locations/{$otherLocation->id}");

    $response->assertForbidden();
});

test('provider cannot access shop dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/shop/dashboard');

    $response->assertForbidden();
});

test('first location is automatically primary', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/shop/locations', [
        'address_line_1' => '123 Main St',
        'city' => 'Portland',
        'state' => 'OR',
        'zip_code' => '97201',
    ]);

    $response->assertCreated();

    expect($response->json('data.is_primary'))->toBeTrue();
});
