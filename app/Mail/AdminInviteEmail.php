<?php

namespace App\Mail;

use App\Models\AdminInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInviteEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AdminInvite $invite,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join '.config('app.name').'!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user.invite',
        );
    }
}
