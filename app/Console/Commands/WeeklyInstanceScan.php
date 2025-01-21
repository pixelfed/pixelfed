<?php

namespace App\Console\Commands;

use App\Instance;
use App\Jobs\InstancePipeline\FetchNodeinfoPipeline;
use Illuminate\Console\Command;

use function Laravel\Prompts\progress;

class WeeklyInstanceScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:weekly-instance-scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan instance nodeinfo';

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
