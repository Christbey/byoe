<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use App\Services\BookingService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('calculateFees returns correct breakdown', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $fees = $bookingService->calculateFees(100.00);

    expect($fees)->toBeArray()
        ->and($fees['service_price'])->toBe(100.00)
        ->and($fees['platform_fee'])->toBe(15.00)
        ->and($fees['provider_payout'])->toBe(85.00);
});

test('platform fee is 15% by default', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $fees = $bookingService->calculateFees(200.00);

    expect($fees['platform_fee'])->toBe(30.00)
        ->and($fees['provider_payout'])->toBe(170.00);
});

test('provider payout equals service price minus platform fee', function () {
    Config::set('marketplace.platform_fee_percentage', 20.0);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $fees = $bookingService->calculateFees(150.00);

    $expectedFee = 30.00; // 20% of 150
    $expectedPayout = 120.00; // 150 - 30

    expect($fees['platform_fee'])->toBe($expectedFee)
        ->and($fees['provider_payout'])->toBe($expectedPayout)
        ->and($fees['service_price'])->toBe(150.00);
});

test('acceptServiceRequest creates booking', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'stripe_payment_intent_id' => 'pi_test123',
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $stripeService->shouldReceive('captureServiceRequestPayment')
        ->once()
        ->andReturn(new \App\Models\Payment);
    $bookingService = new BookingService($stripeService);

    $booking = $bookingService->acceptServiceRequest($serviceRequest, $provider);

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->service_request_id)->toBe($serviceRequest->id)
        ->and($booking->provider_id)->toBe($provider->id)
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->service_price)->toBe(100.0)
        ->and($booking->platform_fee)->toBe(15.0)
        ->and($booking->provider_payout)->toBe(85.0);

    $this->assertDatabaseHas('bookings', [
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('filled');
});

test('acceptServiceRequest skips payment capture for legacy requests without payment intent', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'open',
        'price' => 100.00,
        'stripe_payment_intent_id' => null,
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $stripeService->shouldNotReceive('captureServiceRequestPayment');
    $bookingService = new BookingService($stripeService);

    $booking = $bookingService->acceptServiceRequest($serviceRequest, $provider);

    expect($booking->status)->toBe('confirmed');
});

test('acceptServiceRequest throws exception for non-open request', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'status' => 'filled', // Not open
        'price' => 100.00,
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    expect(fn () => $bookingService->acceptServiceRequest($serviceRequest, $provider))
        ->toThrow(\Exception::class, 'Service request is no longer available');
});

test('completeBooking increments provider completed bookings', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'completed_bookings' => 5,
    ]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $bookingService->completeBooking($booking);

    $provider->refresh();
    expect($provider->completed_bookings)->toBe(6);

    $booking->refresh();
    expect($booking->status)->toBe('completed')
        ->and($booking->completed_at)->not->toBeNull();
});

test('completeBooking throws exception for already completed booking', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    expect(fn () => $bookingService->completeBooking($booking))
        ->toThrow(\Exception::class, 'Booking is already completed');
});

test('cancelBooking within window allows full refund', function () {
    Config::set('marketplace.cancellation.full_refund_hours_before', 48);
    Config::set('marketplace.cancellation.partial_refund_percentage', 50.0);
    Config::set('marketplace.cancellation.no_refund_hours_before', 24);

    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Service is 60 hours away (more than 48 hours)
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
        'service_date' => now()->addHours(60),
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 115.00,
        'status' => 'succeeded',
        'paid_at' => now(),
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $stripeService->shouldReceive('refundPayment')->once()->andReturnUsing(function ($payment) {
        $payment->markAsRefunded();
    });
    $bookingService = new BookingService($stripeService);

    $result = $bookingService->cancelBooking($booking, 'Customer request');

    expect($result)->toBeTrue();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled')
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancellation_reason)->toBe('Customer request');

    $payment->refresh();
    expect($payment->status)->toBe('refunded');
});

test('cancelBooking in partial refund window', function () {
    Config::set('marketplace.cancellation.full_refund_hours_before', 48);
    Config::set('marketplace.cancellation.partial_refund_percentage', 50.0);
    Config::set('marketplace.cancellation.no_refund_hours_before', 24);

    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Service is 36 hours away (between 24 and 48 hours)
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
        'service_date' => now()->addHours(36),
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 100.00,
        'status' => 'succeeded',
        'paid_at' => now(),
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $stripeService->shouldReceive('refundPayment')->once()->andReturnUsing(function ($payment) {
        $payment->markAsRefunded();
    });
    $bookingService = new BookingService($stripeService);

    $result = $bookingService->cancelBooking($booking, 'Customer request');

    expect($result)->toBeTrue();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');

    $payment->refresh();
    expect($payment->status)->toBe('refunded');
});

test('cancelBooking outside window denies refund', function () {
    Config::set('marketplace.cancellation.full_refund_hours_before', 48);
    Config::set('marketplace.cancellation.partial_refund_percentage', 50.0);
    Config::set('marketplace.cancellation.no_refund_hours_before', 24);

    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Service is 12 hours away (less than 24 hours)
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
        'service_date' => now()->addHours(12),
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'confirmed',
    ]);

    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'amount' => 100.00,
        'status' => 'succeeded',
        'paid_at' => now(),
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $result = $bookingService->cancelBooking($booking, 'Customer request');

    expect($result)->toBeTrue();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');

    // No refund, so payment status should still be succeeded
    $payment->refresh();
    expect($payment->status)->toBe('succeeded');
});

test('cancelBooking throws exception for already cancelled booking', function () {
    $user = User::factory()->create();
    $provider = Provider::factory()->create(['user_id' => $user->id]);

    $shopOwner = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $location = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $location->id,
        'price' => 100.00,
        'service_date' => now()->addHours(60),
    ]);

    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    expect(fn () => $bookingService->cancelBooking($booking, 'Test'))
        ->toThrow(\Exception::class, 'Booking is already cancelled');
});

test('config values are respected for fee calculation', function () {
    // Test with different platform fee percentage
    Config::set('marketplace.platform_fee_percentage', 25.0);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    $fees = $bookingService->calculateFees(400.00);

    expect($fees['platform_fee'])->toBe(100.00) // 25% of 400
        ->and($fees['provider_payout'])->toBe(300.00); // 400 - 100
});

test('fee calculation rounds correctly', function () {
    Config::set('marketplace.platform_fee_percentage', 15.0);

    $stripeService = \Mockery::mock(StripeService::class);
    $bookingService = new BookingService($stripeService);

    // Test with amount that could have rounding issues
    $fees = $bookingService->calculateFees(123.45);

    // 15% of 123.45 = 18.5175, should round to 18.52
    expect($fees['platform_fee'])->toBe(18.52)
        ->and($fees['provider_payout'])->toBe(104.93) // 123.45 - 18.52
        ->and($fees['service_price'])->toBe(123.45);
});
