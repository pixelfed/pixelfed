<?php

namespace App\Console\Commands\Internal;

use App\Jobs\InstancePipeline\FetchNodeinfoPipeline;
use App\Models\Instance;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\progress;

#[Signature('app:weekly-instance-scan')]
#[Description('Scan instance nodeinfo')]
class WeeklyInstanceScan extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((bool) config_cache('federation.activitypub.enabled') == false) {
            return;
        }

        $users = progress(
            label: 'Updating instance stats...',
            steps: Instance::all(),
            callback: fn ($instance) => $this->updateInstanceStats($instance),
        );
    }

    protected function updateInstanceStats($instance)
    {
        FetchNodeinfoPipeline::dispatch($instance)->onQueue('intbg');
    }
}
