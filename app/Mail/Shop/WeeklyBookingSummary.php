<?php

namespace App\Mail\Shop;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyBookingSummary extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Shop $shop,
        public Collection $upcomingBookings,
        public Collection $completedBookings,
        public float $totalSpent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your weekly booking summary',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop.weekly-booking-summary',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
