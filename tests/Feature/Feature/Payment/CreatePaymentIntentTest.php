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
use Mockery\MockInterface;

test('shop owner can create payment intent for their booking', function () {
    Config::set('stripe.currency', 'usd');

    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider with Stripe account
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);
    $stripeAccount = ProviderStripeAccount::factory()->create([
        'provider_id' => $provider->id,
        'stripe_account_id' => 'acct_test123',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
    ]);

    // Create service request and booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    // Mock StripeService
    $this->mock(StripeService::class, function (MockInterface $mock) use ($booking) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($booking) {
                return $arg->id === $booking->id;
            }))
            ->andReturn(Payment::factory()->make([
                'id' => 1,
                'booking_id' => $booking->id,
                'stripe_payment_intent_id' => 'pi_test123',
                'amount' => 115.00,
                'currency' => 'usd',
                'status' => 'pending',
            ]));
    });

    $response = $this->actingAs($shopOwner)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'client_secret',
            'amount',
            'payment_id',
        ])
        ->assertJson([
            'client_secret' => 'pi_test123',
            'amount' => 11500, // In cents
        ]);
});

test('creates payment record in database', function () {
    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider with Stripe account
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);
    $stripeAccount = ProviderStripeAccount::factory()->create([
        'provider_id' => $provider->id,
        'stripe_account_id' => 'acct_test123',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
    ]);

    // Create service request and booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    // Mock StripeService to create actual payment
    $this->mock(StripeService::class, function (MockInterface $mock) use ($booking) {
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => 'pi_test456',
            'amount' => 115.00,
            'currency' => 'usd',
            'status' => 'pending',
        ]);

        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn($payment);
    });

    $response = $this->actingAs($shopOwner)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'stripe_payment_intent_id' => 'pi_test456',
        'amount' => 115.00,
        'status' => 'pending',
    ]);
});

test('cannot create payment for non-pending booking', function () {
    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create service request and completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'completed', // Not pending
    ]);

    $response = $this->actingAs($shopOwner)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Booking is not in a payable state.',
        ]);
});

test('cannot create payment for other shop booking', function () {
    // Create two shop owners
    $shopOwner1 = User::factory()->create();
    $shopOwner1->assignRole('shop_owner');
    $shop1 = Shop::factory()->create(['user_id' => $shopOwner1->id]);

    $shopOwner2 = User::factory()->create();
    $shopOwner2->assignRole('shop_owner');
    $shop2 = Shop::factory()->create(['user_id' => $shopOwner2->id]);
    $location2 = ShopLocation::factory()->create(['shop_id' => $shop2->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create service request and booking for shop2
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location2->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    // Shop owner 1 tries to create payment for shop owner 2's booking
    $response = $this->actingAs($shopOwner1)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Unauthorized access to this booking.',
        ]);
});

test('unauthenticated user cannot create payment intent', function () {
    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create service request and booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(401);
});

test('user without shop cannot create payment intent', function () {
    // Create user without shop
    $user = User::factory()->create();
    $user->assignRole('provider');

    // Create another shop owner's booking
    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Shop not found for this user.',
        ]);
});

test('returns correct amount in cents', function () {
    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider with Stripe account
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);
    $stripeAccount = ProviderStripeAccount::factory()->create([
        'provider_id' => $provider->id,
        'stripe_account_id' => 'acct_test123',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
    ]);

    // Create service request and booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 200.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 200.00,
        'platform_fee' => 30.00,
        'provider_payout' => 170.00,
        'status' => 'pending',
    ]);

    // Mock StripeService
    $this->mock(StripeService::class, function (MockInterface $mock) use ($booking) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn(Payment::factory()->make([
                'id' => 1,
                'booking_id' => $booking->id,
                'stripe_payment_intent_id' => 'pi_test789',
                'amount' => 230.00, // service_price + platform_fee
                'currency' => 'usd',
                'status' => 'pending',
            ]));
    });

    $response = $this->actingAs($shopOwner)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(200)
        ->assertJson([
            'amount' => 23000, // 230.00 * 100 = 23000 cents
        ]);
});

test('handles stripe service exceptions gracefully', function () {
    // Create shop owner and shop
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create service request and booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'service_price' => 100.00,
        'platform_fee' => 15.00,
        'provider_payout' => 85.00,
        'status' => 'pending',
    ]);

    // Mock StripeService to throw exception
    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
    });

    $response = $this->actingAs($shopOwner)->postJson("/api/v1/bookings/{$booking->id}/payment-intent");

    $response->assertStatus(500)
        ->assertJson([
            'message' => 'Failed to create payment intent.',
        ]);
});
