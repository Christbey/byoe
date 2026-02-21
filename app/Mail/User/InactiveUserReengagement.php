<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InactiveUserReengagement extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysInactive,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We miss you at ShiftFinder!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user.inactive-user-reengagement',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
