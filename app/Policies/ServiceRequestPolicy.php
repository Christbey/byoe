<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Everyone with a role can view service requests (filtered by their scope)
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager', 'provider']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        // Admins can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        $serviceRequest->loadMissing('shopLocation.shop');

        // Shop owner can view their own requests
        if ($serviceRequest->shopLocation->shop->user_id === $user->id) {
            return true;
        }

        // Providers can view open requests (for browsing available work)
        if ($user->hasRole('provider') && $serviceRequest->status === 'open') {
            return true;
        }

        // Providers can view requests they've been invited to or accepted
        if ($user->hasRole('provider') && $user->provider) {
            // Check if provider has a booking for this request
            $hasBooking = $serviceRequest->booking()
                ->where('provider_id', $user->provider->id)
                ->exists();

            if ($hasBooking) {
                return true;
            }

            // Check if provider was invited
            $wasInvited = $serviceRequest->invitations()
                ->where('provider_id', $user->provider->id)
                ->exists();

            if ($wasInvited) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Shop owners and managers can create service requests
        return $user->hasAnyRole(['admin', 'shop_owner', 'shop_manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        // Admins can always update
        if ($user->hasRole('admin')) {
            return true;
        }

        $serviceRequest->loadMissing('shopLocation.shop');

        // Shop owner can update their own requests (only if still open)
        return $serviceRequest->shopLocation->shop->user_id === $user->id
            && $serviceRequest->status === 'open';
    }

    /**
     * Determine whether the user can delete the model (cancel).
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        // Admins can always cancel
        if ($user->hasRole('admin')) {
            return true;
        }

        $serviceRequest->loadMissing('shopLocation.shop');

        // Shop owner can cancel their own requests (only if not filled)
        return $serviceRequest->shopLocation->shop->user_id === $user->id
            && in_array($serviceRequest->status, ['pending_payment', 'open']);
    }

    /**
     * Determine whether the user can confirm the payment for a service request.
     */
    public function confirmPayment(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $serviceRequest->loadMissing('shopLocation.shop');

        return $serviceRequest->shopLocation->shop->user_id === $user->id;
    }

    /**
     * Determine whether the user can accept the service request.
     *
     * Only checks role-level authorization. Business rule validation (active status,
     * Stripe onboarding, request status, expiry) is handled in BookingService.
     */
    public function accept(User $user, ServiceRequest $serviceRequest): bool
    {
        // Only providers can accept
        if (! $user->hasRole('provider') || ! $user->provider) {
            return false;
        }

        // Provider cannot accept their own shop's requests
        $serviceRequest->loadMissing('shopLocation.shop');

        return $serviceRequest->shopLocation->shop->user_id !== $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->hasRole('admin');
    }
}
