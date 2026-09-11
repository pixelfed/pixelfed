<?php

namespace App\Console\Commands;

use App\Services\Internal\SoftwareUpdateService;
use Illuminate\Console\Command;

class ComposerPostInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:composer-post-install-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post composer install/update housekeeping (refreshes the software update cache)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            SoftwareUpdateService::get(true);
            $this->info('Software update cache refreshed.');
        } catch (\Throwable $e) {
            $this->warn('Could not refresh software update cache: '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
