<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'support', 'ops']);
    }

    public function view(User $user, Dispute $dispute): bool
    {
        if ($user->hasAnyRole(['admin', 'support', 'ops'])) {
            return true;
        }

        $dispute->loadMissing('booking.serviceRequest.shopLocation.shop', 'booking.provider.user');

        // Shop owner can view disputes they created or are involved in
        if ($dispute->booking->serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        // Provider can view disputes they're involved in
        if ($dispute->booking->provider->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Shop owners and providers can create disputes
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager', 'provider']);
    }

    public function update(User $user, Dispute $dispute): bool
    {
        // Only admins and support can update/resolve disputes
        return $user->hasAnyRole(['admin', 'support', 'ops']);
    }

    public function delete(User $user, Dispute $dispute): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Dispute $dispute): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Dispute $dispute): bool
    {
        return $user->hasRole('admin');
    }
}
