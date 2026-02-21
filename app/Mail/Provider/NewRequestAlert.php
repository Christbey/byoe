<?php

namespace App\Mail\Provider;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRequestAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $request,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New shift available: '.$this->request->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider.new-request-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
