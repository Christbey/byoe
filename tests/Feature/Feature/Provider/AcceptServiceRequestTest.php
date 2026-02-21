<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ProviderStripeAccount;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Support\Facades\Config;

/**
 * Helper to create a provider with a fully onboarded Stripe account.
 */
function makeOnboardedProvider(User $user, bool $isActive = true): Provider
{
    $provider = Provider::factory()->create(['user_id' => $user->id, 'is_active' => $isActive]);
    ProviderStripeAccount::factory()->onboarded()->create(['provider_id' => $provider->id]);

    return $provider;
}

test('provider can accept open service request', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'stripe_payment_intent_id' => 'pi_test_123',
        'expires_at' => now()->addDays(2),
    ]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('captureServiceRequestPayment')->andReturn(new Payment);
    });

    $response = $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('bookings', [
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('service_requests', [
        'id' => $serviceRequest->id,
        'status' => 'filled',
    ]);
});

test('creates booking with correct fee calculation', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 200.00,
        'stripe_payment_intent_id' => 'pi_test_456',
        'expires_at' => now()->addDays(2),
    ]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('captureServiceRequestPayment')->andReturn(new Payment);
    });

    $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $booking = Booking::where('service_request_id', $serviceRequest->id)->first();

    expect($booking->service_price)->toBe(200.0);
    expect($booking->platform_fee)->toBe(30.0); // 15% of 200
    expect($booking->provider_payout)->toBe(170.0); // 200 - 30
});

test('marks request as filled after acceptance', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'stripe_payment_intent_id' => 'pi_test_789',
        'expires_at' => now()->addDays(2),
    ]);

    expect($serviceRequest->status)->toBe('open');

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('captureServiceRequestPayment')->andReturn(new Payment);
    });

    $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('filled');
});

test('provider cannot accept already filled request', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'filled',
        'price' => 100.00,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('provider cannot accept expired request', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'expires_at' => now()->subHours(1),
    ]);

    $response = $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('non provider user cannot accept request', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'expires_at' => now()->addDays(2),
    ]);

    // Shop owner doesn't have provider role, should be denied by middleware
    $response = $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertStatus(403);
});

test('inactive provider cannot accept request', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id, 'is_active' => false]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('unauthenticated user cannot accept request', function () {
    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'expires_at' => now()->addDays(2),
    ]);

    $response = $this->post("/provider/requests/{$serviceRequest->id}/accept");

    $response->assertRedirect('/login');
});

test('booking has correct accepted at timestamp', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    makeOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'stripe_payment_intent_id' => 'pi_test_ts',
        'expires_at' => now()->addDays(2),
    ]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('captureServiceRequestPayment')->andReturn(new Payment);
    });

    $beforeAcceptance = now();
    $this->actingAs($user)->post("/provider/requests/{$serviceRequest->id}/accept");
    $afterAcceptance = now();

    $booking = Booking::where('service_request_id', $serviceRequest->id)->first();

    expect($booking->accepted_at)->not->toBeNull();
    expect($booking->accepted_at->timestamp)->toBeGreaterThanOrEqual($beforeAcceptance->timestamp);
    expect($booking->accepted_at->timestamp)->toBeLessThanOrEqual($afterAcceptance->timestamp);
});
