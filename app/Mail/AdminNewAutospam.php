<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Services\AccountService;
use App\Services\StatusService;

class AdminNewAutospam extends Mailable
{
    use Queueable, SerializesModels;

    public $report;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: '[' . config('pixelfed.domain.app') . '] Spam Post Detected',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
    	$data = $this->report->toArray();
    	$reported_status = null;
    	$reported_account = null;
    	$url = url('/i/admin/reports/autospam/' . $this->report->id);

    	if($data['item_type'] === 'App\Status') {
    		$reported_status = StatusService::get($this->report->item_id, false);
    		$reported_account = AccountService::get($reported_status['account']['id'], true);
    	}

        return new Content(
            markdown: 'emails.admin.new_autospam',
            with: [
            	'report' => $data,
            	'url' => $url,
            	'reported_status' => $reported_status,
            	'reported_account' => $reported_account
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
