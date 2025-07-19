<?php

namespace App\Console\Commands;

use App\Services\Internal\SoftwareUpdateService;
use Cache;
use Illuminate\Console\Command;

class SoftwareUpdateRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:software-update-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh latest software version data';

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
