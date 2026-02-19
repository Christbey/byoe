<?php

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\ShopLocation;
use App\Models\User;

uses()->group('provider', 'available-requests');

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('provider');
    $this->provider = Provider::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);
});

test('page renders as inertia response', function () {
    $this->get('/provider/available-requests')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('provider/AvailableRequests'));
});

test('all filter returns open non-expired requests', function () {
    $open = ServiceRequest::factory()->create(['status' => 'open', 'expires_at' => now()->addHours(24)]);
    ServiceRequest::factory()->create(['status' => 'open', 'expires_at' => now()->subHour()]);
    ServiceRequest::factory()->create(['status' => 'filled', 'expires_at' => now()->addHours(24)]);

    $this->get('/provider/available-requests?filter=all')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'all')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $open->id)
        );
});

test('today filter returns only requests with service_date today', function () {
    $today = ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
        'service_date' => today()->toDateString(),
    ]);
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
        'service_date' => today()->addDay()->toDateString(),
    ]);

    $this->get('/provider/available-requests?filter=today')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'today')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $today->id)
        );
});

test('week filter returns requests within the next 7 days', function () {
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
        'service_date' => today()->toDateString(),
    ]);
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addDays(10),
        'service_date' => today()->addDays(6)->toDateString(),
    ]);
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addDays(15),
        'service_date' => today()->addDays(10)->toDateString(),
    ]);

    $this->get('/provider/available-requests?filter=week')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'week')
            ->has('requests.data', 2)
        );
});

test('nearby filter with gps coords returns locationSource gps', function () {
    $location = ShopLocation::factory()->create([
        'latitude' => 34.05,
        'longitude' => -118.25,
    ]);
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
        'shop_location_id' => $location->id,
    ]);

    $this->provider->update(['service_area_max_miles' => 50]);

    $this->get('/provider/available-requests?filter=nearby&lat=34.05&lng=-118.25')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'nearby')
            ->where('locationSource', 'gps')
        );
});

test('nearby filter with no location returns null locationSource', function () {
    $this->provider->update(['preferred_zip_codes' => []]);

    $this->get('/provider/available-requests?filter=nearby')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'nearby')
            ->where('locationSource', null)
        );
});

test('nearby filter excludes requests outside provider service area', function () {
    // New York shop — far from Los Angeles
    $farLocation = ShopLocation::factory()->create([
        'latitude' => 40.71,
        'longitude' => -74.01,
    ]);
    ServiceRequest::factory()->create([
        'status' => 'open',
        'expires_at' => now()->addHours(24),
        'shop_location_id' => $farLocation->id,
    ]);

    $this->provider->update(['service_area_max_miles' => 50]);

    // Query from Los Angeles — New York should not appear
    $this->get('/provider/available-requests?filter=nearby&lat=34.05&lng=-118.25')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('provider/AvailableRequests')
            ->where('filter', 'nearby')
            ->has('requests.data', 0)
        );
});

test('filter defaults to all when not specified', function () {
    $this->get('/provider/available-requests')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filter', 'all'));
});
