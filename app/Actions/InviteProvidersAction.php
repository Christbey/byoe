<?php

namespace App\Actions;

use App\Jobs\SendProviderInvitationNotificationJob;
use App\Models\ProviderInvitation;
use App\Models\ServiceRequest;

class InviteProvidersAction
{
    /**
     * Execute the action.
     *
     * @param ServiceRequest $serviceRequest The service request to invite providers to
     * @param array $providerIds Array of provider IDs to invite
     * @return int The count of invitations sent
     */
    public function __invoke(ServiceRequest $serviceRequest, array $providerIds): int
    {
        $count = 0;

        foreach ($providerIds as $providerId) {
            // Create the provider invitation
            $invitation = ProviderInvitation::create([
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $providerId,
                'status' => 'pending',
                'invited_at' => now(),
            ]);

            // Dispatch notification job
            SendProviderInvitationNotificationJob::dispatch($invitation);

            $count++;
        }

        return $count;
    }
}
