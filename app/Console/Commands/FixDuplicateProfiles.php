<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Like;
use App\Media;
use App\Profile;
use App\Status;
use App\User;

class FixDuplicateProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:profile:duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate profiles';

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
     * @return mixed
     */
    public function handle()
    {
        $profiles = Profile::selectRaw('count(user_id) as count,user_id')->whereNotNull('user_id')->groupBy('user_id')->orderBy('user_id', 'desc')->get()->where('count', '>', 1);
        $count = $profiles->count();
        if($count == 0) {
            $this->info("No duplicate profiles found!");
            return;
        }
        $this->info("Found {$count} accounts with duplicate profiles...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($profiles as $profile) {
            $dup = Profile::whereUserId($profile->user_id)->get();

            if(
                $dup->first()->username === $dup->last()->username && 
                $dup->last()->statuses()->count() == 0 && 
                $dup->last()->followers()->count() == 0 && 
                $dup->last()->likes()->count() == 0 &&
                $dup->last()->media()->count() == 0
            ) {
                $dup->last()->avatar->forceDelete();
                $dup->last()->forceDelete();
            }
            $bar->advance();
        }
        $bar->finish();
    }
}
