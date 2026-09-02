<?php

namespace App\Console\Commands\Internal;

use App\Jobs\InternalPipeline\NotificationEpochUpdatePipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:notification-epoch-update')]
#[Description('Update notification epoch')]
class NotificationEpochUpdate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        NotificationEpochUpdatePipeline::dispatch();
    }
}
