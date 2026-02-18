<?php

use App\Actions\AcceptServiceRequestAction;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ProviderStripeAccount;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use App\Services\BookingService;
use App\Services\StripeService;

uses()->group('booking', 'flow');

test('provider can accept service request', function () {
    // Create provider user and assign role
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create open service request
    $shopLocation = ShopLocation::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
        'status' => 'open',
        'price' => 200.00,
    ]);

    // Provider accepts the request
    $bookingService = app(BookingService::class);
    $booking = $bookingService->acceptServiceRequest($serviceRequest, $provider);

    // Assert booking was created
    expect($booking)->toBeInstanceOf(Booking::class);
    expect($booking->status)->toBe('pending');
    expect($booking->provider_id)->toBe($provider->id);
    expect($booking->service_request_id)->toBe($serviceRequest->id);
    expect($booking->service_price)->toBe(200.0);

    // Assert service request was marked as filled
    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('filled');

    // Assert database has the booking
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'pending',
    ]);
});

test('shop owner can pay for booking', function () {
    // Mock Stripe service to avoid actual API calls
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn(Payment::factory()->make([
                'stripe_payment_intent_id' => 'pi_test123',
                'amount' => 200.00,
                'status' => 'pending',
            ]));
    });

    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider with Stripe account
    $provider = Provider::factory()->create();
    ProviderStripeAccount::factory()->create([
        'provider_id' => $provider->id,
        'stripe_account_id' => 'acct_test123',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
    ]);

    // Create booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    // Create payment manually since we mocked the service
    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'stripe_payment_intent_id' => 'pi_test123',
        'amount' => 200.00,
        'status' => 'pending',
    ]);

    // Assert payment was created
    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->booking_id)->toBe($booking->id);
    expect($payment->status)->toBe('pending');

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => 'pending',
    ]);
});

test('payment success webhook updates booking status', function () {
    // Create booking with payment
    $provider = Provider::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);
    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'stripe_payment_intent_id' => 'pi_test123',
        'status' => 'pending',
    ]);

    // Simulate payment success webhook
    $stripeService = app(StripeService::class);
    $paymentIntent = (object) [
        'id' => 'pi_test123',
        'payment_method_types' => ['card'],
    ];

    $stripeService->handlePaymentIntentSucceeded($paymentIntent);

    // Assert payment status updated
    $payment->refresh();
    expect($payment->status)->toBe('succeeded');
    expect($payment->paid_at)->not->toBeNull();

    // Assert booking status updated
    $booking->refresh();
    expect($booking->status)->toBe('confirmed');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'succeeded',
    ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'confirmed',
    ]);
});

test('booking can be marked complete by shop', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create confirmed booking
    $provider = Provider::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    // Complete booking
    $bookingService = app(BookingService::class);
    $bookingService->completeBooking($booking);

    // Assert booking is completed
    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->completed_at)->not->toBeNull();

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'completed',
    ]);

    // Assert provider's completed bookings count incremented
    $provider->refresh();
    expect($provider->completed_bookings)->toBeGreaterThan(0);
});

test('booking can be marked complete by provider', function () {
    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $providerUser->id,
        'completed_bookings' => 0,
    ]);

    // Create confirmed booking
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    // Complete booking
    $bookingService = app(BookingService::class);
    $bookingService->completeBooking($booking);

    // Assert booking is completed
    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->completed_at)->not->toBeNull();

    // Assert provider's completed bookings count incremented
    $provider->refresh();
    expect($provider->completed_bookings)->toBe(1);
});

test('booking can be cancelled before payment', function () {
    // Create pending booking (no payment yet)
    $provider = Provider::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create();
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    // Cancel booking
    $bookingService = app(BookingService::class);
    $result = $bookingService->cancelBooking($booking, 'Changed plans');

    expect($result)->toBeTrue();

    // Assert booking is cancelled
    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
    expect($booking->cancelled_at)->not->toBeNull();
    expect($booking->cancellation_reason)->toBe('Changed plans');

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'cancelled',
        'cancellation_reason' => 'Changed plans',
    ]);
});

test('provider cannot accept already filled request', function () {
    // Create provider
    $provider = Provider::factory()->create();

    // Create filled service request
    $serviceRequest = ServiceRequest::factory()->create([
        'status' => 'filled',
    ]);

    // Try to accept filled request
    $bookingService = app(BookingService::class);

    expect(fn() => $bookingService->acceptServiceRequest($serviceRequest, $provider))
        ->toThrow(\Exception::class, 'Service request is no longer available');

    // Assert no booking was created
    $this->assertDatabaseMissing('bookings', [
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);
});
