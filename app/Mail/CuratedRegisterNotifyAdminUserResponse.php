<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CuratedRegisterNotifyAdminUserResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $activity;

    /**
     * Create a new message instance.
     */
    public function __construct($activity)
    {
        $this->activity = $activity;
    }
}
