<?php

namespace App\Jobs;

use App\Mail\Provider\ServiceRequestInvitation;
use App\Models\ProviderInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendProviderInvitationNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProviderInvitation $invitation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load relationships
        $this->invitation->loadMissing(['provider.user', 'serviceRequest.shopLocation.shop']);

        // Send email to provider
        Mail::to($this->invitation->provider->user->email)
            ->send(new ServiceRequestInvitation(
                $this->invitation->serviceRequest,
                $this->invitation->provider
            ));

        // Update invitation status to sent
        $this->invitation->update([
            'sent_at' => now(),
        ]);
    }
}
