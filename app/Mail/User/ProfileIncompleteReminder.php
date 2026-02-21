<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileIncompleteReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $userType,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Complete your profile to get started',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user.profile-incomplete-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
