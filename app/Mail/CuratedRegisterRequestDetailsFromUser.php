<?php

namespace App\Mail;

use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratedRegisterRequestDetailsFromUser extends Mailable
{
    use Queueable, SerializesModels;

    public $verify;

    public $activity;

    /**
     * Create a new message instance.
     */
    public function __construct(CuratedRegister $verify, CuratedRegisterActivity $activity)
    {
        $this->verify = $verify;
        $this->activity = $activity;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Action Needed]: Additional information requested',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.curated-register.request-details-from-user',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
