<?php

namespace App\Console\Commands\Internal;

use App\Models\FailedJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gc:failedjobs')]
#[Description('Delete failed jobs over 1 month old')]
class GarbageCollectorFailedJob extends Command
{
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
                if ($job->failed_at->lt(now()->subHours(48))) {
                    $job->delete();
                }
            }
        });
    }
}
