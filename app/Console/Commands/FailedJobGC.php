<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\FailedJob;

class FailedJobGC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc:failedjobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete failed jobs over 1 month old';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        FailedJob::chunk(50, function ($jobs) {
            foreach ($jobs as $job) {
                if ($job->failed_at->lt(now()->subMonth())) {
                    $job->delete();
                }
            }
        });
    }
}
