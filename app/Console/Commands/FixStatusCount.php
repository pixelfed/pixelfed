<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Profile;
use App\Services\AccountService;

class FixStatusCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:statuscount {--remote} {--resync} {--remote-only} {--dlog}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix profile status count';

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
     * @return int
     */
    public function handle()
    {
        if(!$this->confirm('Are you sure you want to run the fix status command?')) {
            return;
        }
        $this->line(' ');
        $this->info('Running fix status command...');
        $now = now();

        $nulls = ['domain', 'status', 'last_fetched_at'];

        $resync = $this->option('resync');
        $resync24hours = false;

        if($resync) {
            $resyncChoices = ['Only resync accounts that havent been synced in 24 hours', 'Resync all accounts'];
            $rsc = $this->choice(
                'Do you want to resync all accounts, or just accounts that havent been resynced for 24 hours?',
                $resyncChoices,
                0
            );
            $rsci = array_search($rsc, $resyncChoices);
            if($rsci === 0) {
                $resync24hours = true;
                $nulls = ['status', 'domain', 'last_fetched_at'];
            } else {
                $resync24hours = false;
                $nulls = ['status', 'domain'];
            }
        }

        $remote = $this->option('remote');

        if($remote) {
            $ni = array_search('domain', $nulls);
            unset($nulls[$ni]);
            $ni = array_search('last_fetched_at', $nulls);
            unset($nulls[$ni]);
        }

        $remoteOnly = $this->option('remote-only');

        if($remoteOnly) {
            $ni = array_search('domain', $nulls);
            unset($nulls[$ni]);
            $ni = array_search('last_fetched_at', $nulls);
            unset($nulls[$ni]);
            $nulls[] = 'user_id';
        }

        $dlog = $this->option('dlog');

        $nulls = array_values($nulls);

        foreach(
            Profile::when($resync24hours, function($query, $resync24hours) use($nulls) {
                if(in_array('domain', $nulls)) {
                    return $query->whereNull('domain')
                        ->whereNull('last_fetched_at')
                        ->orWhere('last_fetched_at', '<', now()->subHours(24));
                } else {
                    return $query->whereNull('last_fetched_at')
                        ->orWhere('last_fetched_at', '<', now()->subHours(24));
                }
            })
            ->when($remoteOnly, function($query, $remoteOnly) {
                return $query->whereNull('last_fetched_at')
                    ->orWhere('last_fetched_at', '<', now()->subHours(24));
            })
            ->whereNull($nulls)
            ->lazyById(50, 'id') as $profile
        ) {
            $ogc = $profile->status_count;
            $upc = $profile->statuses()
            ->getQuery()
            ->whereIn('scope', ['public', 'private', 'unlisted'])
            ->count();
            if($ogc != $upc) {
                $profile->status_count = $upc;
                $profile->last_fetched_at = $now;
                $profile->save();
                AccountService::del($profile->id);
                if($dlog) {
                    $this->info($profile->id . ':' . $profile->username . ' : ' . $upc);
                }
            } else {
                $profile->last_fetched_at = $now;
                $profile->save();
                if($dlog) {
                    $this->info($profile->id . ':' . $profile->username . ' : ' . $upc);
                }
            }
        }

        $this->line(' ');
        $this->info('Finished fix status count command!');

        return 0;
    }
}
