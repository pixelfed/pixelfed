<?php

namespace App\Console\Commands\Internal;

use App\Models\AppRegister;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cleanup-expired-app-registrations')]
#[Description('Command description')]
class CleanupExpiredAppRegistrations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        AppRegister::where('created_at', '<', now()->subDays(90))->delete();
    }
}
