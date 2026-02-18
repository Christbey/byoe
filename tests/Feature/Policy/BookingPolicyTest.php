<?php

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

uses()->group('policy', 'booking');

test('shop owner can view their bookings', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create booking for this shop
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
    ]);

    // Act as shop owner
    $this->actingAs($shopOwner);

    // Assert shop owner can view the booking
    expect(Gate::allows('view', $booking))->toBeTrue();
    expect($shopOwner->can('view', $booking))->toBeTrue();
});

test('shop owner cannot view other bookings', function () {
    // Create two shop owners
    $shopOwner1 = User::factory()->create();
    $shopOwner1->assignRole('shop_owner');
    $shop1 = Shop::factory()->create(['user_id' => $shopOwner1->id]);
    $shopLocation1 = ShopLocation::factory()->create(['shop_id' => $shop1->id]);

    $shopOwner2 = User::factory()->create();
    $shopOwner2->assignRole('shop_owner');
    $shop2 = Shop::factory()->create(['user_id' => $shopOwner2->id]);
    $shopLocation2 = ShopLocation::factory()->create(['shop_id' => $shop2->id]);

    // Create booking for shop 2
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation2->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
    ]);

    // Act as shop owner 1
    $this->actingAs($shopOwner1);

    // Assert shop owner 1 cannot view shop 2's booking
    expect(Gate::denies('view', $booking))->toBeTrue();
    expect($shopOwner1->cannot('view', $booking))->toBeTrue();
});

test('provider can view their bookings', function () {
    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create booking for this provider
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Act as provider
    $this->actingAs($providerUser);

    // Assert provider can view the booking
    expect(Gate::allows('view', $booking))->toBeTrue();
    expect($providerUser->can('view', $booking))->toBeTrue();
});

test('provider cannot view other provider bookings', function () {
    // Create two providers
    $providerUser1 = User::factory()->create();
    $providerUser1->assignRole('provider');
    $provider1 = Provider::factory()->create(['user_id' => $providerUser1->id]);

    $providerUser2 = User::factory()->create();
    $providerUser2->assignRole('provider');
    $provider2 = Provider::factory()->create(['user_id' => $providerUser2->id]);

    // Create booking for provider 2
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider2->id,
    ]);

    // Act as provider 1
    $this->actingAs($providerUser1);

    // Assert provider 1 cannot view provider 2's booking
    expect(Gate::denies('view', $booking))->toBeTrue();
    expect($providerUser1->cannot('view', $booking))->toBeTrue();
});

test('admin can view all bookings', function () {
    // Create admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create booking from any shop/provider
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
    ]);

    // Act as admin
    $this->actingAs($admin);

    // Assert admin can view any booking
    expect(Gate::allows('view', $booking))->toBeTrue();
    expect($admin->can('view', $booking))->toBeTrue();
});

test('provider can cancel pending booking', function () {
    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create pending booking
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    // Act as provider
    $this->actingAs($providerUser);

    // Assert provider can cancel pending booking
    expect(Gate::allows('delete', $booking))->toBeTrue();
    expect($providerUser->can('delete', $booking))->toBeTrue();
});

test('provider cannot cancel confirmed booking', function () {
    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create confirmed booking
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    // Act as provider
    $this->actingAs($providerUser);

    // Assert provider cannot cancel confirmed booking
    expect(Gate::denies('delete', $booking))->toBeTrue();
    expect($providerUser->cannot('delete', $booking))->toBeTrue();
});

test('shop owner can cancel their booking', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create booking for this shop (any status)
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'status' => 'confirmed',
    ]);

    // Act as shop owner
    $this->actingAs($shopOwner);

    // Assert shop owner can cancel their booking
    expect(Gate::allows('delete', $booking))->toBeTrue();
    expect($shopOwner->can('delete', $booking))->toBeTrue();
});
