<?php

namespace App\Mail\Shop;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMethodExpiring extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Shop $shop,
        public string $lastFour,
        public string $expiryDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment method ending in '.$this->lastFour.' expires soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop.payment-method-expiring',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
