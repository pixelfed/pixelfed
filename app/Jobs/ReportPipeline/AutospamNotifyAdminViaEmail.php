<?php

namespace App\Jobs\ReportPipeline;

use App\Mail\AdminNewAutospam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AutospamNotifyAdminViaEmail implements ShouldQueue
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

        if (config('instance.reports.email.enabled') == false || empty($addresses) || ! config('instance.reports.email.autospam')) {
            return;
        }

        if (strpos($addresses, ',')) {
            $to = explode(',', $addresses);
        } else {
            $to = $addresses;
        }

        Mail::to($to)->send(new AdminNewAutospam($this->report));
    }
}
