<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseSessionGarbageCollector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc:sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database sessions garbage collector';

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
    	if(config('session.driver') !== 'database') {
        	return Command::SUCCESS;
    	}

    	DB::transaction(function() {
    		DB::table('sessions')->whereNull('user_id')->delete();
    	});

    	DB::transaction(function() {
    		$ts = now()->subMonths(3)->timestamp;
    		DB::table('sessions')->where('last_activity', '<', $ts)->delete();
    	});

        return Command::SUCCESS;
    }
}
