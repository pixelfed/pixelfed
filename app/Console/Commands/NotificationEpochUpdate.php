<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\InternalPipeline\NotificationEpochUpdatePipeline;

class NotificationEpochUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-epoch-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update notification epoch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        NotificationEpochUpdatePipeline::dispatch();
    }
}
