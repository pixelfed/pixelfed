<?php

namespace App\Console\Commands;

use App\Follower;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Profile;
use Illuminate\Console\Command;

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

        for ($i = 0; $i < $limit; $i++) {
            try {
                $actor = Profile::inRandomOrder()->firstOrFail();
                $target = Profile::inRandomOrder()->firstOrFail();

                $follow = new Follower();
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
