<?php

namespace App\Mail\Provider;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRatingReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Rating $rating,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You received a '.$this->rating->rating.'-star rating',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider.new-rating-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
