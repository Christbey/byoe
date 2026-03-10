<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;

test('provider can complete a pending booking', function () {
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
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->post("/provider/bookings/{$booking->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->completed_at)->not->toBeNull();
});

test('provider cannot complete another provider\'s booking', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id]);

    $otherUser = User::factory()->create();
    $otherUser->assignRole('provider');
    $otherProvider = Provider::factory()->create(['user_id' => $otherUser->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);
    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $otherProvider->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->post("/provider/bookings/{$booking->id}/complete");

    $response->assertStatus(403);
});

test('provider cannot complete an already completed booking', function () {
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
        'status' => 'completed',
        'completed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($user)->post("/provider/bookings/{$booking->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('completing booking recalculates provider completed bookings count from actual bookings', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $user->id, 'completed_bookings' => 0]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);
    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);

    Booking::factory()->count(5)->completed()->create([
        'provider_id' => $provider->id,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)->post("/provider/bookings/{$booking->id}/complete");

    $provider->refresh();
    expect($provider->completed_bookings)->toBe(6);
});

test('unauthenticated user cannot complete booking', function () {
    $provider = Provider::factory()->create();
    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);
    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    $response = $this->post("/provider/bookings/{$booking->id}/complete");

    $response->assertRedirect('/login');
});
