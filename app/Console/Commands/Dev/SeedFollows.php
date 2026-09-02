<?php

namespace App\Console\Commands\Dev;

use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\Follower;
use App\Models\Profile;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('seed:follows')]
#[Description('Seed follows for testing')]
class SeedFollows extends Command
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
     * @return mixed
     */
    public function handle()
    {
        $limit = 100;

        for ($i = 0; $i < $limit; $i++) {
            try {
                $actor = Profile::whereDomain(false)->inRandomOrder()->firstOrFail();
                $target = Profile::whereDomain(false)->inRandomOrder()->firstOrFail();

                if ($actor->id == $target->id) {
                    continue;
                }

                $follow = Follower::firstOrCreate([
                    'profile_id' => $actor->id,
                    'following_id' => $target->id,
                ]);
                if ($follow->wasRecentlyCreated == true) {
                    FollowPipeline::dispatch($follow);
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
