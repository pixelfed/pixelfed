<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminMessage extends Mailable
{
    use Queueable, SerializesModels;

    protected $msg;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $admins = config('pixelfed.domain.app') . ' admins';
        return $this->markdown('emails.notification.admin_message')
            ->with(['msg' => $this->msg])
            ->subject('Message from ' . $admins);
    }
}
