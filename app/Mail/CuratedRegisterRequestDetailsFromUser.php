<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;

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
}
