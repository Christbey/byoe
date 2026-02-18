<?php

namespace App\Mail\Provider;

use App\Models\Provider;
use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceRequestInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ServiceRequest $serviceRequest,
        public Provider $provider
    ) {
        // Eager load relationships
        $this->serviceRequest->load('shopLocation.shop');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $shopName = $this->serviceRequest->shopLocation->shop->name;

        return new Envelope(
            subject: "New Service Opportunity at {$shopName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.provider.service-request-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
