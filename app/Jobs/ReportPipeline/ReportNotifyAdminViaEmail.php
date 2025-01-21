<?php

namespace App\Jobs\ReportPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\AdminNewReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReportNotifyAdminViaEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $report;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $addresses = config('instance.reports.email.to');

        if(config('instance.reports.email.enabled') == false || empty($addresses)) {
        	return;
        }

        if(strpos($addresses, ',')) {
        	$to = explode(',', $addresses);
        } else {
        	$to = $addresses;
        }

        Mail::to($to)->send(new AdminNewReport($this->report));
    }
}
