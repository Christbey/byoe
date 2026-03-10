<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Log;

uses()->group('stripe', 'webhook');

test('webhook with valid signature is processed', function () {
    // Create test data
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

    // Mock Stripe webhook signature verification
    $payload = json_encode([
        'id' => 'evt_test123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test123',
                'object' => 'payment_intent',
                'payment_method_types' => ['card'],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $secret = config('stripe.webhook_secret');
    config(['stripe.webhook_secret' => 'whsec_test_secret']);

    // Generate valid signature
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret');
    $sigHeader = "t={$timestamp},v1={$signature}";

    // Send webhook request
    $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true, 512, JSON_THROW_ON_ERROR), [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'success']);

    // Restore original config
    config(['stripe.webhook_secret' => $secret]);
});

test('webhook with invalid signature is rejected', function () {
    config(['stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = json_encode([
        'id' => 'evt_test123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test123',
                'object' => 'payment_intent',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    // Invalid signature
    $sigHeader = 't='.time().',v1=invalid_signature_hash';

    $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true, 512, JSON_THROW_ON_ERROR), [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid signature']);
});

test('webhook without signature is rejected', function () {
    config(['stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = json_encode([
        'id' => 'evt_test123',
        'object' => 'event',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test123',
                'object' => 'payment_intent',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    // No Stripe-Signature header
    $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true, 512, JSON_THROW_ON_ERROR));

    $response->assertStatus(400);
});

test('unhandled webhook event is logged', function () {
    Log::spy();

    $payload = json_encode([
        'id' => 'evt_test123',
        'object' => 'event',
        'type' => 'customer.created',
        'data' => [
            'object' => [
                'id' => 'cus_test123',
                'object' => 'customer',
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    config(['stripe.webhook_secret' => 'whsec_test_secret']);

    // Generate valid signature
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret');
    $sigHeader = "t={$timestamp},v1={$signature}";

    $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true, 512, JSON_THROW_ON_ERROR), [
        'Stripe-Signature' => $sigHeader,
    ]);

    $response->assertStatus(200);

    // Verify unhandled event was logged
    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function ($message, $context) {
            return $message === 'Stripe webhook: unhandled event type' &&
                   $context['event_type'] === 'customer.created';
        });
});
