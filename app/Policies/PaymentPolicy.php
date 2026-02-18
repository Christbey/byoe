<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager', 'provider']);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $payment->loadMissing('booking.serviceRequest.shopLocation.shop', 'booking.provider.user');

        // Shop owner can view their payments
        if ($payment->booking->serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        // Provider can view payments for their bookings
        if ($payment->booking->provider->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only shop owners can create payments (for their bookings)
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager']);
    }

    public function update(User $user, Payment $payment): bool
    {
        // Payments are immutable once created, only admins can update
        return $user->hasRole('admin');
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Payments cannot be deleted, only refunded
        return false;
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }
}
