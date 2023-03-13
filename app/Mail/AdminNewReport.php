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

class AdminNewReport extends Mailable
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
    	$type = $this->report->type;
    	$id = $this->report->id;
    	$object_type = last(explode("\\", $this->report->object_type));
        return new Envelope(
            subject: '[' . config('pixelfed.domain.app') . '] ' . $object_type . ' Report (#' . $id . '-' . $type . ')',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
    	$report = $this->report;
    	$object_type = last(explode("\\", $this->report->object_type));
    	$reporter = AccountService::get($report->profile_id, true);
    	$reported = AccountService::get($report->reported_profile_id, true);
    	$title = 'New ' . $object_type . ' Report (#' . $report->id . ')';
    	$reportUrl = url('/i/admin/reports/show/' . $report->id . '?ref=email');
    	$data = [
    		'report' => $report,
    		'object_type' => $object_type,
    		'title' => $title,
    		'reporter' => $reporter,
    		'reported' => $reported,
    		'url' => $reportUrl,
    		'message' => 'You have a new moderation report.'
    	];

    	if($object_type === 'Status') {
    		$data['reported_status'] = StatusService::get($report['object_id'], false);
    		if($reporter && $reported) {
    			$data['message'] = '<a href="' .  url('/i/web/profile/' . $reporter['id']) . '">@' .
	    			$reporter['acct'] . '</a> reported a post by <a href="' . url('/i/web/profile/' . $reported['id']) .
	    			'">@' . $reported['acct'] . '</a> as ' . $report->type . '.';
    		}
    	}

    	if($object_type === 'Profile') {
    		if($reporter && $reported) {
    		$data['message'] = '<a href="' .  url('/i/web/profile/' . $reporter['id']) . '">@' .
    			$reporter['acct'] . '</a> reported <a href="' . url('/i/web/profile/' . $reported['id']) .
    			'">@' . $reported['acct'] . '</a>\'s profile as ' . $report->type . '.';
    		}
    	}

        return new Content(
            markdown: 'emails.admin.new_report',
            with: $data
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
