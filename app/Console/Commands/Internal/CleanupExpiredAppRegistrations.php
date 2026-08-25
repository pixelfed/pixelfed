<?php

namespace App\Console\Commands\Internal;

use App\Models\AppRegister;
use Illuminate\Console\Command;

class CleanupExpiredAppRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-app-registrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        AppRegister::where('created_at', '<', now()->subDays(90))->delete();
    }
}
