<?php

use App\Models\Shop;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;

uses()->group('stripe', 'customer');

test('recreates customer when stripe customer is deleted', function () {
    Log::spy();

    // Create shop with an old customer ID
    $shop = Shop::factory()->create([
        'stripe_customer_id' => 'cus_deleted123',
    ]);

    // Mock Stripe Customer retrieve to throw "resource not found" exception
    $retrieveMock = mock('alias:'.Customer::class);
    $retrieveMock->shouldReceive('retrieve')
        ->with('cus_deleted123')
        ->once()
        ->andThrow(new InvalidRequestException('No such customer: cus_deleted123'));

    // Mock Stripe Customer create to return a new customer
    $newCustomer = new stdClass;
    $newCustomer->id = 'cus_new456';

    $retrieveMock->shouldReceive('create')
        ->once()
        ->with([
            'email' => $shop->user->email,
            'name' => $shop->name,
            'metadata' => [
                'shop_id' => $shop->id,
                'user_id' => $shop->user_id,
            ],
        ])
        ->andReturn($newCustomer);

    $stripeService = new StripeService;
    $customerId = $stripeService->createOrRetrieveShopCustomer($shop);

    // Verify new customer ID was returned and saved
    expect($customerId)->toBe('cus_new456');
    expect($shop->fresh()->stripe_customer_id)->toBe('cus_new456');

    // Verify warning was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Shop customer deleted from Stripe, recreating' &&
                   $context['old_customer_id'] === 'cus_deleted123';
        });
});

test('returns existing customer when it still exists in stripe', function () {
    $shop = Shop::factory()->create([
        'stripe_customer_id' => 'cus_existing123',
    ]);

    // Mock Stripe Customer retrieve to succeed
    $existingCustomer = new stdClass;
    $existingCustomer->id = 'cus_existing123';

    $retrieveMock = mock('alias:'.Customer::class);
    $retrieveMock->shouldReceive('retrieve')
        ->with('cus_existing123')
        ->once()
        ->andReturn($existingCustomer);

    $stripeService = new StripeService;
    $customerId = $stripeService->createOrRetrieveShopCustomer($shop);

    // Verify existing customer ID was returned
    expect($customerId)->toBe('cus_existing123');
    expect($shop->fresh()->stripe_customer_id)->toBe('cus_existing123');
});

test('creates new customer when shop has no customer id', function () {
    $shop = Shop::factory()->create([
        'stripe_customer_id' => null,
    ]);

    // Mock Stripe Customer create
    $newCustomer = new stdClass;
    $newCustomer->id = 'cus_new789';

    $createMock = mock('alias:'.Customer::class);
    $createMock->shouldReceive('create')
        ->once()
        ->with([
            'email' => $shop->user->email,
            'name' => $shop->name,
            'metadata' => [
                'shop_id' => $shop->id,
                'user_id' => $shop->user_id,
            ],
        ])
        ->andReturn($newCustomer);

    $stripeService = new StripeService;
    $customerId = $stripeService->createOrRetrieveShopCustomer($shop);

    // Verify new customer ID was returned and saved
    expect($customerId)->toBe('cus_new789');
    expect($shop->fresh()->stripe_customer_id)->toBe('cus_new789');
});
