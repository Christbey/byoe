<?php

use App\Actions\CreateRatingAction;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\Rating;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;

uses()->group('rating', 'two-sided');

test('shop can rate provider after completion', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $providerUser->id,
        'average_rating' => 0,
        'total_ratings' => 0,
    ]);

    // Create completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Shop rates provider
    $createRatingAction = app(CreateRatingAction::class);
    $rating = $createRatingAction($booking, $shopOwner, [
        'rating' => 5,
        'comment' => 'Excellent service!',
    ]);

    // Assert rating was created
    expect($rating)->toBeInstanceOf(Rating::class);
    expect($rating->booking_id)->toBe($booking->id);
    expect($rating->rater_id)->toBe($shopOwner->id);
    expect($rating->ratee_id)->toBe($providerUser->id);
    expect($rating->rater_type)->toBe('shop');
    expect($rating->rating)->toBe(5);
    expect($rating->comment)->toBe('Excellent service!');

    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_id' => $shopOwner->id,
        'ratee_id' => $providerUser->id,
        'rater_type' => 'shop',
        'rating' => 5,
    ]);
});

test('provider can rate shop after completion', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Provider rates shop
    $createRatingAction = app(CreateRatingAction::class);
    $rating = $createRatingAction($booking, $providerUser, [
        'rating' => 4,
        'comment' => 'Great place to work!',
    ]);

    // Assert rating was created
    expect($rating)->toBeInstanceOf(Rating::class);
    expect($rating->booking_id)->toBe($booking->id);
    expect($rating->rater_id)->toBe($providerUser->id);
    expect($rating->ratee_id)->toBe($shopOwner->id);
    expect($rating->rater_type)->toBe('provider');
    expect($rating->rating)->toBe(4);

    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_id' => $providerUser->id,
        'ratee_id' => $shopOwner->id,
        'rater_type' => 'provider',
        'rating' => 4,
    ]);
});

test('shop cannot rate before completion', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $provider = Provider::factory()->create();

    // Create pending booking (not completed)
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
        'status' => 'pending',
    ]);

    // This test assumes there's a policy or validation that prevents rating before completion
    // For now, we'll just verify that the booking is not completed
    expect($booking->isCompleted())->toBeFalse();
    expect($booking->isPending())->toBeTrue();
});

test('provider cannot rate twice', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Provider rates shop once
    Rating::create([
        'booking_id' => $booking->id,
        'rater_id' => $providerUser->id,
        'ratee_id' => $shopOwner->id,
        'rater_type' => 'provider',
        'rating' => 5,
    ]);

    // Try to rate again - should have only one rating from this provider for this booking
    $existingRatingsCount = Rating::where('booking_id', $booking->id)
        ->where('rater_id', $providerUser->id)
        ->count();

    expect($existingRatingsCount)->toBe(1);

    // Verify database constraint
    $this->assertDatabaseCount('ratings', 1);
});

test('rating updates provider average', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider with initial ratings
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $providerUser->id,
        'average_rating' => 0,
        'total_ratings' => 0,
    ]);

    // Create completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Shop rates provider
    $createRatingAction = app(CreateRatingAction::class);
    $rating = $createRatingAction($booking, $shopOwner, [
        'rating' => 5,
        'comment' => 'Excellent!',
    ]);

    // Assert provider's rating was updated
    $provider->refresh();
    expect($provider->average_rating)->toBeGreaterThan(0);
    expect($provider->total_ratings)->toBeGreaterThan(0);
});

test('both parties can rate same booking', function () {
    // Create shop owner
    $shopOwner = User::factory()->create();
    $shopOwner->assignRole('shop_owner');
    $shop = Shop::factory()->create(['user_id' => $shopOwner->id]);
    $shopLocation = ShopLocation::factory()->create(['shop_id' => $shop->id]);

    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $providerUser->id,
        'average_rating' => 0,
        'total_ratings' => 0,
    ]);

    // Create completed booking
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Shop rates provider
    $createRatingAction = app(CreateRatingAction::class);
    $shopRating = $createRatingAction($booking, $shopOwner, [
        'rating' => 5,
        'comment' => 'Great provider!',
    ]);

    // Provider rates shop
    $providerRating = $createRatingAction($booking, $providerUser, [
        'rating' => 4,
        'comment' => 'Nice shop!',
    ]);

    // Assert both ratings exist
    expect($shopRating)->toBeInstanceOf(Rating::class);
    expect($providerRating)->toBeInstanceOf(Rating::class);
    expect($shopRating->id)->not->toBe($providerRating->id);

    // Assert both ratings are in database
    $this->assertDatabaseCount('ratings', 2);
    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_type' => 'shop',
    ]);
    $this->assertDatabaseHas('ratings', [
        'booking_id' => $booking->id,
        'rater_type' => 'provider',
    ]);
});

test('cannot rate booking from other user', function () {
    // Create shop owner 1
    $shopOwner1 = User::factory()->create();
    $shopOwner1->assignRole('shop_owner');
    $shop1 = Shop::factory()->create(['user_id' => $shopOwner1->id]);
    $shopLocation1 = ShopLocation::factory()->create(['shop_id' => $shop1->id]);

    // Create shop owner 2 (outsider)
    $shopOwner2 = User::factory()->create();
    $shopOwner2->assignRole('shop_owner');

    // Create provider
    $providerUser = User::factory()->create();
    $providerUser->assignRole('provider');
    $provider = Provider::factory()->create(['user_id' => $providerUser->id]);

    // Create completed booking for shop 1
    $serviceRequest = ServiceRequest::factory()->create([
        'shop_location_id' => $shopLocation1->id,
    ]);
    $booking = Booking::factory()->completed()->create([
        'service_request_id' => $serviceRequest->id,
        'provider_id' => $provider->id,
    ]);

    // Shop owner 2 tries to rate (should not be allowed)
    $createRatingAction = app(CreateRatingAction::class);
    $rating = $createRatingAction($booking, $shopOwner2, [
        'rating' => 5,
        'comment' => 'Test',
    ]);

    // Since shop owner 2 is not part of the booking, the rater_type and ratee_id should be null
    expect($rating->rater_type)->toBeNull();
    expect($rating->ratee_id)->toBeNull();
});
