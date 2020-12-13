<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Profile;
use App\User;

class FixSoftDeletedProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:sdprofile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $profiles = Profile::whereNull('domain')
            ->withTrashed()
            ->where('deleted_at', '>', now()->subDays(14))
            ->whereNull('status')
            ->pluck('username');

        if($profiles->count() == 0) {
            return 0;
        }

        foreach($profiles as $p) {
            if(User::whereUsername($p)->first()->status == null) {
                $pro = Profile::withTrashed()->whereUsername($p)->firstOrFail();
                $pro->deleted_at = null;
                $pro->save();
            }
        }
    }
}
