<?php

use App\Models\Payment;
use App\Models\Provider;
use App\Models\ProviderStripeAccount;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

// Helper function for creating an onboarded provider
function createApiOnboardedProvider(User $user, bool $isActive = true): Provider
{
    $provider = Provider::factory()->create(['user_id' => $user->id, 'is_active' => $isActive]);
    ProviderStripeAccount::factory()->onboarded()->create(['provider_id' => $provider->id]);

    return $provider;
}

test('provider can accept open service request via API', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = createApiOnboardedProvider($user);

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

    Sanctum::actingAs($user, ['provider:accept']);

    $response = $this->postJson("/api/v1/provider/service-requests/{$serviceRequest->id}/accept");

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'provider' => ['id'],
                'service_request',
            ],
        ]);

    expect($response->json('data.status'))->toBe('confirmed');

    $this->assertDatabaseHas('bookings', [
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);
});

test('API creates booking with correct fee calculation', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = createApiOnboardedProvider($user);

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

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/provider/service-requests/{$serviceRequest->id}/accept");

    $response->assertCreated();

    expect((float) $response->json('data.service_price'))->toBe(200.0)
        ->and((float) $response->json('data.platform_fee'))->toBe(30.0)
        ->and((float) $response->json('data.provider_payout'))->toBe(170.0);
});

test('inactive provider cannot accept request via API', function () {
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

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/provider/service-requests/{$serviceRequest->id}/accept");

    $response->assertForbidden()
        ->assertJson(['message' => 'Provider account is not active.']);
});

test('provider without Stripe account cannot accept request via API', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    Provider::factory()->create(['user_id' => $user->id, 'is_active' => true]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'expires_at' => now()->addDays(2),
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson("/api/v1/provider/service-requests/{$serviceRequest->id}/accept");

    $response->assertForbidden()
        ->assertJson(['message' => 'Complete Stripe onboarding before accepting requests.']);
});

test('provider can get available requests via API', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = createApiOnboardedProvider($user);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    ServiceRequest::factory()->count(5)->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'expires_at' => now()->addDays(2),
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/provider/available-requests');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'status'],
            ],
        ]);
});

test('provider can get dashboard via API', function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = createApiOnboardedProvider($user);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/provider/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'provider' => [
                'id',
                'bio',
                'skills',
                'average_rating',
                'can_receive_payouts',
                'profile_completeness',
            ],
            'upcoming_bookings',
            'recent_completed_bookings',
            'available_requests',
            'stats',
        ]);

    expect($response->json('provider.can_receive_payouts'))->toBeTrue();
});

test('non-provider cannot access provider dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('shop_owner');

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/provider/dashboard');

    $response->assertForbidden();
});
