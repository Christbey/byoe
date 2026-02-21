<?php

use App\Jobs\ExpireServiceRequestsJob;
use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\BookingService;

uses()->group('service-request', 'expiration');

test('expired requests are marked expired', function () {
    // Create expired service request
    $serviceRequest = ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->subHours(2),
    ]);

    expect($serviceRequest->status)->toBe('open');
    expect($serviceRequest->expires_at->isPast())->toBeTrue();

    // Run expiration job
    $job = new ExpireServiceRequestsJob;
    $job->handle();

    // Assert request was marked as expired
    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('expired');

    $this->assertDatabaseHas('service_requests', [
        'id' => $serviceRequest->id,
        'status' => 'expired',
    ]);
});

test('future requests are not expired', function () {
    // Create future service request
    $serviceRequest = ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
    ]);

    expect($serviceRequest->status)->toBe('open');
    expect($serviceRequest->expires_at->isFuture())->toBeTrue();

    // Run expiration job
    $job = new ExpireServiceRequestsJob;
    $job->handle();

    // Assert request is still open
    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('open');

    $this->assertDatabaseHas('service_requests', [
        'id' => $serviceRequest->id,
        'status' => 'open',
    ]);
});

test('expired requests cannot be accepted', function () {
    // Create provider
    $provider = Provider::factory()->create();

    // Create expired service request
    $serviceRequest = ServiceRequest::factory()->create([
        'status' => 'expired',
        'expires_at' => now()->subHours(2),
    ]);

    // Try to accept expired request
    $bookingService = app(BookingService::class);

    expect(fn () => $bookingService->acceptServiceRequest($serviceRequest, $provider))
        ->toThrow(\Exception::class, 'Service request is no longer available');

    // Assert no booking was created
    $this->assertDatabaseMissing('bookings', [
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);
});

test('expiration job processes multiple requests', function () {
    // Create 5 expired requests
    $expiredRequests = ServiceRequest::factory()->count(5)->create([
        'status' => 'open',
        'expires_at' => now()->subHours(1),
    ]);

    // Create 3 future requests
    $futureRequests = ServiceRequest::factory()->count(3)->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
    ]);

    // Verify initial state
    expect(ServiceRequest::where('status', 'open')->count())->toBe(8);
    expect(ServiceRequest::where('status', 'expired')->count())->toBe(0);

    // Run expiration job
    $job = new ExpireServiceRequestsJob;
    $job->handle();

    // Assert only expired requests were marked
    expect(ServiceRequest::where('status', 'expired')->count())->toBe(5);
    expect(ServiceRequest::where('status', 'open')->count())->toBe(3);

    // Verify each expired request
    foreach ($expiredRequests as $request) {
        $request->refresh();
        expect($request->status)->toBe('expired');
    }

    // Verify future requests are still open
    foreach ($futureRequests as $request) {
        $request->refresh();
        expect($request->status)->toBe('open');
    }
});

test('already filled requests are not expired', function () {
    // Create filled service request that is past expiration date
    $serviceRequest = ServiceRequest::factory()->create([
        'status' => 'filled',
        'expires_at' => now()->subHours(5),
    ]);

    expect($serviceRequest->status)->toBe('filled');
    expect($serviceRequest->expires_at->isPast())->toBeTrue();

    // Run expiration job
    $job = new ExpireServiceRequestsJob;
    $job->handle();

    // Assert request status is unchanged
    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe('filled');

    $this->assertDatabaseHas('service_requests', [
        'id' => $serviceRequest->id,
        'status' => 'filled',
    ]);
});
