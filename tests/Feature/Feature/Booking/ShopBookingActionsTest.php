<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;

// ── Helpers ──────────────────────────────────────────────────────────────────

function shopWithBooking(string $bookingStatus = 'confirmed'): array
{
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'filled',
        'expires_at' => now()->addDays(7),
        'service_date' => now()->addDays(3),
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => $bookingStatus,
    ]);

    return compact('shopOwner', 'shop', 'location', 'provider', 'providerUser', 'serviceRequest', 'booking');
}

// ── Cancel Booking ────────────────────────────────────────────────────────────

test('shop owner can cancel their booking', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('confirmed');

    $response = $this->actingAs($shopOwner)->delete("/shop/bookings/{$booking->id}");

    $response->assertRedirect(route('shop.bookings.index'));
    $response->assertSessionHas('success');

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancelled_at)->not->toBeNull();
    expect($booking->cancellation_reason)->toBe('Cancelled by shop');
});

test('shop owner cannot cancel another shops booking', function () {
    ['booking' => $booking] = shopWithBooking('confirmed');

    $otherOwner = User::factory()->create();
    $otherOwner->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $otherOwner->id]);

    $response = $this->actingAs($otherOwner)->delete("/shop/bookings/{$booking->id}");

    $response->assertStatus(403);
});

test('shop owner cannot cancel an already cancelled booking', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('cancelled');

    $response = $this->actingAs($shopOwner)->delete("/shop/bookings/{$booking->id}");

    $response->assertRedirect();
    expect(session('errors')?->first('booking'))->not->toBeNull();
});

test('shop owner cannot cancel a completed booking', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('completed');

    $response = $this->actingAs($shopOwner)->delete("/shop/bookings/{$booking->id}");

    $response->assertRedirect();
    expect(session('errors')?->first('booking'))->not->toBeNull();
});

test('unauthenticated user cannot cancel a booking', function () {
    ['booking' => $booking] = shopWithBooking('confirmed');

    $response = $this->delete("/shop/bookings/{$booking->id}");

    $response->assertRedirect('/login');
});

// ── Complete Booking ──────────────────────────────────────────────────────────

test('shop owner can complete a confirmed booking', function () {
    [
        'shopOwner' => $shopOwner,
        'provider' => $provider,
        'serviceRequest' => $serviceRequest,
        'booking' => $booking,
    ] = shopWithBooking('confirmed');

    Booking::factory()->count(4)->completed()->create([
        'provider_id' => $provider->id,
    ]);

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->completed_at)->not->toBeNull();

    $provider->refresh();
    expect($provider->completed_bookings)->toBe(5);
});

test('shop owner cannot complete another shops booking', function () {
    ['booking' => $booking] = shopWithBooking('confirmed');

    $otherOwner = User::factory()->create();
    $otherOwner->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $otherOwner->id]);

    $response = $this->actingAs($otherOwner)->post("/shop/bookings/{$booking->id}/complete");

    $response->assertStatus(403);
});

test('shop owner cannot complete an already completed booking', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('completed');

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/complete");

    $response->assertRedirect();
    expect(session('errors')?->first('booking'))->not->toBeNull();
});

test('shop owner cannot complete a cancelled booking', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('cancelled');

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/complete");

    $response->assertRedirect();
    expect(session('errors')?->first('booking'))->not->toBeNull();
});

// ── Rate Provider ─────────────────────────────────────────────────────────────

test('shop owner can rate a provider after booking is completed', function () {
    [
        'shopOwner' => $shopOwner,
        'provider' => $provider,
        'booking' => $booking,
    ] = shopWithBooking('completed');

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/rate", [
        'rating' => 5,
        'comment' => 'Great work!',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_id' => $shopOwner->id,
        'ratee_id' => $provider->user_id,
        'rater_type' => 'shop',
        'rating' => 5,
        'comment' => 'Great work!',
    ]);
});

test('shop owner cannot rate a booking that is not completed', function () {
    [
        'shopOwner' => $shopOwner,
        'booking' => $booking,
    ] = shopWithBooking('confirmed');

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/rate", [
        'rating' => 4,
    ]);

    $response->assertRedirect();
    expect(session('errors')?->first('booking'))->not->toBeNull();
});

test('shop owner cannot rate the same booking twice', function () {
    [
        'shopOwner' => $shopOwner,
        'provider' => $provider,
        'booking' => $booking,
    ] = shopWithBooking('completed');

    $booking->ratings()->create([
        'rater_id' => $shopOwner->id,
        'ratee_id' => $provider->user_id,
        'rater_type' => 'shop',
        'rating' => 5,
        'comment' => 'First rating',
    ]);

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/rate", [
        'rating' => 3,
    ]);

    $response->assertRedirect();
    expect(session('errors')?->first('rating'))->not->toBeNull();
});

test('shop owner cannot rate another shops booking', function () {
    ['booking' => $booking] = shopWithBooking('completed');

    $otherOwner = User::factory()->create();
    $otherOwner->assignRole('shop_owner');
    Shop::factory()->create(['user_id' => $otherOwner->id]);

    $response = $this->actingAs($otherOwner)->post("/shop/bookings/{$booking->id}/rate", [
        'rating' => 3,
    ]);

    $response->assertStatus(403);
});

test('rating updates provider average rating', function () {
    [
        'shopOwner' => $shopOwner,
        'provider' => $provider,
        'booking' => $booking,
    ] = shopWithBooking('completed');

    $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/rate", [
        'rating' => 4,
    ]);

    $provider->refresh();
    expect($provider->average_rating)->toBe(4.0);
    expect($provider->total_ratings)->toBe(1);
});
