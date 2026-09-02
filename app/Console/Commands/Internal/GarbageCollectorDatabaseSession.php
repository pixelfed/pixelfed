<?php

namespace App\Console\Commands\Internal;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('gc:sessions')]
#[Description('Database sessions garbage collector')]
class GarbageCollectorDatabaseSession extends Command
{
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
        if (config('session.driver') !== 'database') {
            return Command::SUCCESS;
        }

        DB::transaction(function () {
            DB::table('sessions')->whereNull('user_id')->delete();
        });

        DB::transaction(function () {
            $ts = now()->subMonths(3)->timestamp;
            DB::table('sessions')->where('last_activity', '<', $ts)->delete();
        });

        return Command::SUCCESS;
    }
}
