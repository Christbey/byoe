<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('shop owner can list their bookings via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    $provider = Provider::factory()->create();
    Booking::factory()->create(['service_request_id' => $serviceRequest->id, 'provider_id' => $provider->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/bookings');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'service_price'],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

test('provider can list their bookings via API', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    Booking::factory()->create(['service_request_id' => $serviceRequest->id, 'provider_id' => $provider->id]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/bookings');

    $response->assertOk();
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

test('shop owner can complete booking via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    $provider = Provider::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/complete");

    $response->assertOk();

    $booking->refresh();
    expect($booking->status)->toBe('completed')
        ->and($booking->completed_at)->not()->toBeNull();
});

test('provider can complete booking via API', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/complete");

    $response->assertOk();

    $booking->refresh();
    expect($booking->status)->toBe('completed');
});

test('shop owner can cancel booking via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'service_date' => now()->addDays(10),
    ]);
    $provider = Provider::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
        'reason' => 'No longer needed',
    ]);

    $response->assertOk();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled')
        ->and($booking->cancellation_reason)->toBe('No longer needed');
});

test('shop owner can rate provider via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'completed',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/rate", [
        'rating' => 5,
        'comment' => 'Excellent service!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'rating', 'comment', 'rater_type'],
        ]);

    expect($response->json('data.rating'))->toBe(5)
        ->and($response->json('data.rater_type'))->toBe('shop');

    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_id' => $user->id,
        'rating' => 5,
    ]);
});

test('provider can rate shop via API', function () {
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'completed',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/rate", [
        'rating' => 4,
        'comment' => 'Good experience',
    ]);

    $response->assertCreated();

    expect($response->json('data.rating'))->toBe(4)
        ->and($response->json('data.rater_type'))->toBe('provider');
});

test('user cannot rate booking twice via API', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'completed',
    ]);

    Sanctum::actingAs($user, ['*']);

    // First rating
    $this->postJson("/api/v1/bookings/{$booking->id}/rate", [
        'rating' => 5,
        'comment' => 'Great!',
    ])->assertCreated();

    // Second rating should fail
    $response = $this->postJson("/api/v1/bookings/{$booking->id}/rate", [
        'rating' => 4,
        'comment' => 'Changed my mind',
    ]);

    $response->assertUnprocessable()
        ->assertJson(['message' => 'You have already rated this booking.']);
});

test('unauthenticated user cannot access bookings', function () {
    $response = $this->getJson('/api/v1/bookings');

    $response->assertUnauthorized();
});
