<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProviderRiskDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $providers,
        public int $pendingReviewCount,
        public int $openDisputeCount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Provider Risk Digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.provider-risk-digest',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
