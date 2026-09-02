<?php

namespace App\Console\Commands\Internal;

use App\Services\Internal\SoftwareUpdateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('app:software-update-refresh')]
#[Description('Refresh latest software version data')]
class SoftwareUpdateRefresh extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $key = SoftwareUpdateService::cacheKey();
        Cache::forget($key);
        Cache::remember($key, 1209600, function () {
            return SoftwareUpdateService::fetchLatest();
        });
        $this->info('Succesfully updated software versions!');
    }
}
