<?php

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Provider;
use App\Models\Rating;
use App\Models\Shop;

test('provider trust metrics reflect ratings cancellations and disputes', function () {
    $provider = Provider::factory()->approved()->create([
        'average_rating' => 0,
        'total_ratings' => 0,
        'completed_bookings' => 0,
        'trust_score' => 0,
        'reliability_score' => 100,
        'cancellation_count' => 0,
        'cancellation_rate' => 0,
        'no_show_count' => 0,
        'dispute_count' => 0,
        'completed_without_issue_count' => 0,
    ]);

    $shop = Shop::factory()->create();

    $completedBooking = Booking::factory()->completed()->create([
        'provider_id' => $provider->id,
    ]);

    $cancelledBooking = Booking::factory()->cancelled()->create([
        'provider_id' => $provider->id,
        'cancellation_reason' => 'Provider no-show at shift start',
    ]);

    Rating::factory()->create([
        'booking_id' => $completedBooking->id,
        'rater_id' => $shop->user_id,
        'ratee_id' => $provider->user_id,
        'rater_type' => 'shop',
        'rating' => 5,
    ]);

    Dispute::factory()->create([
        'booking_id' => $cancelledBooking->id,
        'filed_by_user_id' => $shop->user_id,
        'dispute_type' => 'no_show',
        'status' => 'open',
    ]);

    $provider->updateRatingAggregates();
    $provider->refreshTrustMetrics();
    $provider->refresh();

    expect($provider->completed_bookings)->toBe(1)
        ->and($provider->cancellation_count)->toBe(1)
        ->and($provider->no_show_count)->toBe(1)
        ->and($provider->dispute_count)->toBe(1)
        ->and($provider->completed_without_issue_count)->toBe(1)
        ->and($provider->cancellation_rate)->toBe(50.0)
        ->and($provider->trust_score)->toBeGreaterThan(0)
        ->and($provider->reliability_score)->toBeLessThan(100)
        ->and($provider->trust_tier)->not->toBe('');
});

test('provider trust metrics auto-refresh when booking rating and dispute records change', function () {
    $provider = Provider::factory()->approved()->create([
        'completed_bookings' => 0,
        'average_rating' => 0,
        'total_ratings' => 0,
        'trust_score' => 0,
        'reliability_score' => 100,
    ]);

    $shop = Shop::factory()->create();

    $completedBooking = Booking::factory()->completed()->create([
        'provider_id' => $provider->id,
    ]);

    $booking = Booking::factory()->create([
        'provider_id' => $provider->id,
        'status' => 'pending',
        'completed_at' => null,
        'cancelled_at' => null,
        'cancellation_reason' => null,
    ]);

    $provider->refresh();

    expect($provider->completed_bookings)->toBe(1);

    Rating::factory()->create([
        'booking_id' => $booking->id,
        'rater_id' => $shop->user_id,
        'ratee_id' => $provider->user_id,
        'rater_type' => 'shop',
        'rating' => 4,
    ]);

    $provider->refresh();

    expect($provider->average_rating)->toBe(4.0)
        ->and($provider->total_ratings)->toBe(1)
        ->and($provider->trust_score)->toBeGreaterThan(0);

    Dispute::factory()->create([
        'booking_id' => $booking->id,
        'filed_by_user_id' => $shop->user_id,
        'dispute_type' => 'service_quality',
        'status' => 'open',
    ]);

    $provider->refresh();

    expect($provider->dispute_count)->toBe(1)
        ->and($provider->completed_without_issue_count)->toBe(1)
        ->and($provider->reliability_score)->toBeLessThan(100);

    $booking->cancel('Provider no-show during shift');
    $provider->refresh();

    expect($provider->cancellation_count)->toBe(1)
        ->and($provider->no_show_count)->toBe(1)
        ->and($provider->cancellation_rate)->toBe(50.0);
});
