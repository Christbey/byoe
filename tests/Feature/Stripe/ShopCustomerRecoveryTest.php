<?php

use App\Models\Shop;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\InvalidRequestException;

uses()->group('stripe', 'customer');

test('recreates customer when stripe customer is deleted', function () {
    Log::spy();

    // Create shop with an old customer ID
    $shop = Shop::factory()->create([
        'stripe_customer_id' => 'cus_deleted123',
    ]);

    $expectedPayload = [
        'email' => $shop->user->email,
        'name' => $shop->name,
        'metadata' => [
            'shop_id' => $shop->id,
            'user_id' => $shop->user_id,
        ],
    ];

    $stripeService = new class($expectedPayload) extends StripeService
    {
        public function __construct(private array $expectedPayload)
        {
            parent::__construct();
        }

        protected function retrieveStripeCustomer(string $customerId): mixed
        {
            expect($customerId)->toBe('cus_deleted123');

            throw new InvalidRequestException('No such customer: cus_deleted123');
        }

        protected function createStripeCustomer(array $payload): mixed
        {
            expect($payload)->toEqual($this->expectedPayload);

            return (object) ['id' => 'cus_new456'];
        }
    };

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

    $stripeService = new class extends StripeService
    {
        protected function retrieveStripeCustomer(string $customerId): mixed
        {
            expect($customerId)->toBe('cus_existing123');

            return (object) ['id' => 'cus_existing123'];
        }

        protected function createStripeCustomer(array $payload): mixed
        {
            throw new RuntimeException('createStripeCustomer should not be called for existing customers');
        }
    };

    $customerId = $stripeService->createOrRetrieveShopCustomer($shop);

    // Verify existing customer ID was returned
    expect($customerId)->toBe('cus_existing123');
    expect($shop->fresh()->stripe_customer_id)->toBe('cus_existing123');
});

test('creates new customer when shop has no customer id', function () {
    $shop = Shop::factory()->create([
        'stripe_customer_id' => null,
    ]);

    $expectedPayload = [
        'email' => $shop->user->email,
        'name' => $shop->name,
        'metadata' => [
            'shop_id' => $shop->id,
            'user_id' => $shop->user_id,
        ],
    ];

    $stripeService = new class($expectedPayload) extends StripeService
    {
        public function __construct(private array $expectedPayload)
        {
            parent::__construct();
        }

        protected function retrieveStripeCustomer(string $customerId): mixed
        {
            throw new RuntimeException('retrieveStripeCustomer should not be called when customer id is null');
        }

        protected function createStripeCustomer(array $payload): mixed
        {
            expect($payload)->toEqual($this->expectedPayload);

            return (object) ['id' => 'cus_new789'];
        }
    };

    $customerId = $stripeService->createOrRetrieveShopCustomer($shop);

    // Verify new customer ID was returned and saved
    expect($customerId)->toBe('cus_new789');
    expect($shop->fresh()->stripe_customer_id)->toBe('cus_new789');
});
