<?php

namespace App\Mail\Booking;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $recipient
    ) {
        $this->booking->load([
            'serviceRequest.shopLocation.shop',
            'provider.user',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking Confirmation #{$this->booking->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
