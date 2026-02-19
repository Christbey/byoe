<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;

test('debug non-pending response', function () {
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);
    $provider = Provider::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create(['shop_location_id' => $location->id]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'completed',
    ]);
    $response = $this->actingAs($shopOwner)->postJson("/api/payments/bookings/{$booking->id}/payment-intent");
    fwrite(STDERR, 'Response: '.json_encode($response->json())."\n");
    fwrite(STDERR, 'Status: '.$response->status()."\n");
    expect(true)->toBeTrue();
});
