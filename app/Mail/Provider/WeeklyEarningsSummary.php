<?php

namespace App\Mail\Provider;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyEarningsSummary extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Provider $provider,
        public float $weeklyEarnings,
        public int $completedBookings,
        public float $averageRating,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly earnings: $'.number_format($this->weeklyEarnings, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider.weekly-earnings-summary',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
