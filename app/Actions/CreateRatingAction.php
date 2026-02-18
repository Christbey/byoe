<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;

class CreateRatingAction
{
    /**
     * Execute the action.
     *
     * @param Booking $booking The booking being rated
     * @param User $rater The user giving the rating
     * @param array $validated The validated rating data
     * @return Rating The created rating
     */
    public function __invoke(Booking $booking, User $rater, array $validated): Rating
    {
        // Determine rater type and ratee
        $raterType = null;
        $rateeId = null;

        if ($rater->isShopOwner() && $booking->serviceRequest->shopLocation->shop->user_id === $rater->id) {
            // Shop owner is rating the provider
            $raterType = 'shop';
            $rateeId = $booking->provider->user_id;
        } elseif ($rater->isProvider() && $booking->provider->user_id === $rater->id) {
            // Provider is rating the shop owner
            $raterType = 'provider';
            $rateeId = $booking->serviceRequest->shopLocation->shop->user_id;
        }

        // Create the rating
        $rating = Rating::create([
            'booking_id' => $booking->id,
            'rater_id' => $rater->id,
            'ratee_id' => $rateeId,
            'rater_type' => $raterType,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        // Update provider's average rating if provider was rated
        if ($raterType === 'shop') {
            $booking->provider->updateRatingAggregates();
        }

        return $rating;
    }
}
