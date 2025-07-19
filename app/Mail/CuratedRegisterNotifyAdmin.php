<?php

namespace App\Mail;

use App\Models\CuratedRegister;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratedRegisterNotifyAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $verify;

    /**
     * Create a new message instance.
     */
    public function __construct(CuratedRegister $verify)
    {
        $this->verify = $verify;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Requires Action]: New Curated Onboarding Application',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.curated-register.admin_notify',
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
