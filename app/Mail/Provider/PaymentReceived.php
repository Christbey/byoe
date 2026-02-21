<?php

namespace App\Mail\Provider;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payout $payout,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment of $'.number_format($this->payout->amount, 2).' processed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider.payment-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
