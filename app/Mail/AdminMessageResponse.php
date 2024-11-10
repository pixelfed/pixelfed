<?php

namespace App\Mail;

use App\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AdminMessageResponse extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contact $contact,
    ) {}

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        $mid = $this->contact->getMessageId();

        return new Headers(
            messageId: $mid,
            text: [
                'X-Entity-Ref-ID' => $mid,
            ],
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ucfirst(strtolower(config('pixelfed.domain.app'))).' Contact Form Response [Ticket #'.$this->contact->id.']',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.admin-response',
            with: [
                'url' => $this->contact->userResponseUrl(),
            ],
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
