<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;

test('shop owner can create a dispute from booking details', function () {
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->approved()->create(['user_id' => $providerUser->id]);

    $serviceRequest = ServiceRequest::factory()->filled()->create(['shop_location_id' => $location->id]);
    $booking = Booking::factory()->confirmed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    $response = $this->actingAs($shopOwner)->post("/shop/bookings/{$booking->id}/disputes", [
        'dispute_type' => 'service_quality',
        'description' => 'Provider arrived late and the shift quality did not meet expectations.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('disputes', [
        'booking_id' => $booking->id,
        'filed_by_user_id' => $shopOwner->id,
        'dispute_type' => 'service_quality',
        'status' => 'open',
    ]);
});

test('provider cannot create duplicate active disputes for the same booking', function () {
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->approved()->create(['user_id' => $providerUser->id]);

    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);
    $serviceRequest = ServiceRequest::factory()->filled()->create(['shop_location_id' => $location->id]);

    $booking = Booking::factory()->confirmed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    $booking->disputes()->create([
        'filed_by_user_id' => $providerUser->id,
        'dispute_type' => 'payment',
        'description' => 'Initial dispute already filed with support.',
        'status' => 'open',
    ]);

    $response = $this->actingAs($providerUser)->post("/provider/bookings/{$booking->id}/disputes", [
        'dispute_type' => 'payment',
        'description' => 'Trying to file the same dispute twice should be blocked.',
    ]);

    $response->assertRedirect();
    expect(session('errors')?->first('dispute'))->not->toBeNull();
    expect($booking->disputes()->count())->toBe(1);
});
