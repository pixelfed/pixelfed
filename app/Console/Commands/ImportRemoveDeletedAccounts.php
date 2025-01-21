<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\User;
use App\Models\ImportPost;
use App\Services\ImportService;

class ImportRemoveDeletedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-remove-deleted-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    const CACHE_KEY = 'pf:services:import:gc-accounts:skip_min_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $skipMinId = Cache::remember(self::CACHE_KEY, 864000, function() {
            return 1;
        });

        $deletedIds = User::withTrashed()
            ->whereNotNull('status')
            ->whereIn('status', ['deleted', 'delete'])
            ->where('id', '>', $skipMinId)
            ->limit(500)
            ->pluck('id');

        if(!$deletedIds || !$deletedIds->count()) {
            return;
        }

        foreach($deletedIds as $did) {
            if(Storage::exists('imports/' . $did)) {
                Storage::deleteDirectory('imports/' . $did);
            }

            ImportPost::where('user_id', $did)->delete();
            $skipMinId = $did;
        }

        Cache::put(self::CACHE_KEY, $skipMinId, 864000);
    }
}
