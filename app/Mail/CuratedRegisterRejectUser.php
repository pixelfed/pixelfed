<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratedRegisterRejectUser extends Mailable
{
    use Queueable, SerializesModels;

    public $verify;

    /**
     * Create a new message instance.
     */
    public function __construct($verify)
    {
        $this->verify = $verify;
    }
}
