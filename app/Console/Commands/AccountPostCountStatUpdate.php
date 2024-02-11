<?php

namespace App\Console\Commands;

use App\Profile;
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use App\Status;
use Illuminate\Console\Command;

class AccountPostCountStatUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:account-post-count-stat-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update post counts from recent activities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ids = AccountStatService::getAllPostCountIncr();
        if (! $ids || ! count($ids)) {
            return;
        }
        foreach ($ids as $id) {
            $acct = AccountService::get($id, true);
            if (! $acct) {
                AccountStatService::removeFromPostCount($id);

                continue;
            }
            $statusCount = Status::whereProfileId($id)->count();
            if ($statusCount != $acct['statuses_count']) {
                $profile = Profile::find($id);
                if (! $profile) {
                    AccountStatService::removeFromPostCount($id);

                    continue;
                }
                $profile->status_count = $statusCount;
                $profile->save();
                AccountService::del($id);
            }
            AccountStatService::removeFromPostCount($id);
        }

    }
}
