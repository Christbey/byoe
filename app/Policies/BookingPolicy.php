<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins, shop owners, and providers can view their bookings
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager', 'provider']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Admins can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Load relationships if not already loaded
        $booking->loadMissing(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Provider can view their own bookings
        if ($user->id === $booking->provider->user_id) {
            return true;
        }

        // Shop owner can view bookings for their shops
        if ($booking->serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only providers can create bookings (by accepting service requests)
        return $user->hasRole('provider');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Only admins can directly update bookings
        // Status changes go through specific methods (complete, cancel, etc.)
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model (cancel).
     */
    public function delete(User $user, Booking $booking): bool
    {
        // Admins can always cancel
        if ($user->hasRole('admin')) {
            return true;
        }

        $booking->loadMissing(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Shop owner can cancel their bookings
        if ($booking->serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        // Provider can cancel their bookings before they're confirmed
        if ($user->id === $booking->provider->user_id && $booking->status === 'pending') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can complete the booking.
     */
    public function complete(User $user, Booking $booking): bool
    {
        // Admins can always complete
        if ($user->hasRole('admin')) {
            return true;
        }

        $booking->loadMissing(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Both shop owner and provider can mark as complete
        return $booking->serviceRequest->shopLocation->shop->user_id === $user->id
            || $user->id === $booking->provider->user_id;
    }

    /**
     * Determine whether the user can rate the booking.
     */
    public function rate(User $user, Booking $booking): bool
    {
        $booking->loadMissing(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Shop owner can rate the provider
        if ($booking->serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        // Provider can rate the shop
        if ($user->id === $booking->provider->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }
}
