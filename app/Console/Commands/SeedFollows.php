<?php

namespace App\Console\Commands;

use App\{Follower, Profile};
use Illuminate\Console\Command;
use App\Jobs\FollowPipeline\FollowPipeline;

class SeedFollows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:follows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed follows for testing';

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
        $limit = 10000;

        for ($i=0; $i < $limit; $i++) { 
            try {
                $actor = Profile::orderByRaw('rand()')->firstOrFail();
                $target = Profile::orderByRaw('rand()')->firstOrFail();

                $follow = new Follower;
                $follow->profile_id = $actor->id;
                $follow->following_id = $target->id;
                $follow->save();

                FollowPipeline::dispatch($follow);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
