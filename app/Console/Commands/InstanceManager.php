<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Instance;
use App\Profile;
use App\Services\InstanceService;
use App\Jobs\InstancePipeline\FetchNodeinfoPipeline;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\search;
use function Laravel\Prompts\table;

class InstanceManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:instance-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Instances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = select(
            'What action do you want to perform?',
            [
                'Recalculate Stats',
                'Ban Instance',
                'Unlist Instance',
                'Unlisted Instances',
                'Banned Instances',
                'Unban Instance',
                'Relist Instance',
            ],
        );

        switch($action) {
            case 'Recalculate Stats':
                return $this->recalculateStats();
            break;

            case 'Unlisted Instances':
                return $this->viewUnlistedInstances();
            break;

            case 'Banned Instances':
                return $this->viewBannedInstances();
            break;

            case 'Unlist Instance':
                return $this->unlistInstance();
            break;

            case 'Ban Instance':
                return $this->banInstance();
            break;

            case 'Unban Instance':
                return $this->unbanInstance();
            break;

            case 'Relist Instance':
                return $this->relistInstance();
            break;
        }
    }

    protected function recalculateStats()
    {
        $instanceCount = Instance::count();
        $confirmed = confirm('Do you want to recalculate stats for all ' . $instanceCount . ' instances?');
        if(!$confirmed) {
            $this->error('Aborting...');
            exit;
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

    protected function unlistInstance()
    {
        $id = search(
            'Search by domain',
            fn (string $value) => strlen($value) > 0
                ? Instance::whereUnlisted(false)->where('domain', 'like', "%{$value}%")->pluck('domain', 'id')->all()
                : []
        );

        $instance = Instance::find($id);
        if(!$instance) {
            $this->error('Oops, an error occured');
            exit;
        }

        $tbl = [
            [
                $instance->domain,
                number_format($instance->status_count),
                number_format($instance->user_count),
            ]
        ];
        table(
            ['Domain', 'Status Count', 'User Count'],
            $tbl
        );

        $confirmed = confirm('Are you sure you want to unlist this instance?');
        if(!$confirmed) {
            $this->error('Aborting instance unlisting');
            exit;
        }

        $instance->unlisted = true;
        $instance->save();
        InstanceService::refresh();
        $this->info('Successfully unlisted ' . $instance->domain . '!');
        exit;
    }

    protected function relistInstance()
    {
        $id = search(
            'Search by domain',
            fn (string $value) => strlen($value) > 0
                ? Instance::whereUnlisted(true)->where('domain', 'like', "%{$value}%")->pluck('domain', 'id')->all()
                : []
        );

        $instance = Instance::find($id);
        if(!$instance) {
            $this->error('Oops, an error occured');
            exit;
        }

        $tbl = [
            [
                $instance->domain,
                number_format($instance->status_count),
                number_format($instance->user_count),
            ]
        ];
        table(
            ['Domain', 'Status Count', 'User Count'],
            $tbl
        );

        $confirmed = confirm('Are you sure you want to re-list this instance?');
        if(!$confirmed) {
            $this->error('Aborting instance re-listing');
            exit;
        }

        $instance->unlisted = false;
        $instance->save();
        InstanceService::refresh();
        $this->info('Successfully re-listed ' . $instance->domain . '!');
        exit;
    }

    protected function banInstance()
    {
        $id = search(
            'Search by domain',
            fn (string $value) => strlen($value) > 0
                ? Instance::whereBanned(false)->where('domain', 'like', "%{$value}%")->pluck('domain', 'id')->all()
                : []
        );

        $instance = Instance::find($id);
        if(!$instance) {
            $this->error('Oops, an error occured');
            exit;
        }

        $tbl = [
            [
                $instance->domain,
                number_format($instance->status_count),
                number_format($instance->user_count),
            ]
        ];
        table(
            ['Domain', 'Status Count', 'User Count'],
            $tbl
        );

        $confirmed = confirm('Are you sure you want to ban this instance?');
        if(!$confirmed) {
            $this->error('Aborting instance ban');
            exit;
        }

        $instance->banned = true;
        $instance->save();
        InstanceService::refresh();
        $this->info('Successfully banned ' . $instance->domain . '!');
        exit;
    }

    protected function unbanInstance()
    {
        $id = search(
            'Search by domain',
            fn (string $value) => strlen($value) > 0
                ? Instance::whereBanned(true)->where('domain', 'like', "%{$value}%")->pluck('domain', 'id')->all()
                : []
        );

        $instance = Instance::find($id);
        if(!$instance) {
            $this->error('Oops, an error occured');
            exit;
        }

        $tbl = [
            [
                $instance->domain,
                number_format($instance->status_count),
                number_format($instance->user_count),
            ]
        ];
        table(
            ['Domain', 'Status Count', 'User Count'],
            $tbl
        );

        $confirmed = confirm('Are you sure you want to unban this instance?');
        if(!$confirmed) {
            $this->error('Aborting instance unban');
            exit;
        }

        $instance->banned = false;
        $instance->save();
        InstanceService::refresh();
        $this->info('Successfully un-banned ' . $instance->domain . '!');
        exit;
    }

    protected function viewBannedInstances()
    {
        $data = Instance::whereBanned(true)
            ->get(['domain', 'user_count', 'status_count'])
            ->map(function($d) {
                return [
                    'domain' => $d->domain,
                    'user_count' => number_format($d->user_count),
                    'status_count' => number_format($d->status_count),
                ];
            })
            ->toArray();
        table(
            ['Domain', 'User Count', 'Status Count'],
            $data
        );
    }

    protected function viewUnlistedInstances()
    {
        $data = Instance::whereUnlisted(true)
            ->get(['domain', 'user_count', 'status_count', 'banned'])
            ->map(function($d) {
                return [
                    'domain' => $d->domain,
                    'user_count' => number_format($d->user_count),
                    'status_count' => number_format($d->status_count),
                    'banned' => $d->banned ? '✅' : null
                ];
            })
            ->toArray();
        table(
            ['Domain', 'User Count', 'Status Count', 'Banned'],
            $data
        );
    }
}
