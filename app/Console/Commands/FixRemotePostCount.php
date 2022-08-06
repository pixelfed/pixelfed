<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Profile;
use App\Status;

class FixRemotePostCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:rpc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix remote accounts post count';

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
        Profile::whereNotNull('domain')->chunk(50, function($profiles) {
            foreach($profiles as $profile) {
                $count = Status::whereNull(['in_reply_to_id', 'reblog_of_id'])->whereProfileId($profile->id)->count();
                $this->info("Checking {$profile->id} {$profile->username} - found {$count} statuses");
                $profile->status_count = $count;
                $profile->save();
            }
        });

        return 0;
    }
}
