<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmAppEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $verify;
    public $appUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($verify, $url)
    {
        $this->verify = $verify;
        $this->appUrl = $url;
    }
}
