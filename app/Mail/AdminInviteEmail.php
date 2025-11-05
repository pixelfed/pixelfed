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
}
