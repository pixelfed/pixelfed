<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\CuratedRegister;

class CuratedRegisterConfirmEmail extends Mailable
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
}
