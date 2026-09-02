<?php

namespace App\Console\Commands\Internal;

use App\Models\Profile;
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:account-post-count-stat-update')]
#[Description('Update post counts from recent activities')]
class AccountPostCountStatUpdate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = 100;
        $lastId = 0;

        while (true) {
            $ids = AccountStatService::getPostCountChunk($lastId, $chunkSize);

            if (empty($ids)) {
                break;
            }

            foreach ($ids as $id) {
                $this->processAccount($id);
                $lastId = $id;
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return 0;
    }

    private function processAccount($id)
    {
        $acct = AccountService::get($id, true);
        if (! $acct) {
            AccountStatService::removeFromPostCount($id);

            return;
        }

        $profile = Profile::find($id);
        if (! $profile) {
            AccountStatService::removeFromPostCount($id);

            return;
        }

        // Reconcile only the status_count column (this queue is fed by
        // status create/delete events). Shared recompute logic lives in
        // AccountStatService so it stays consistent with admin:fixProfileCounts.
        AccountStatService::reconcileProfileCounts($profile, ['statuses']);

        AccountStatService::removeFromPostCount($id);
    }
}
