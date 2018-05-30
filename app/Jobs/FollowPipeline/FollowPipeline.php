<?php

namespace App\Jobs\FollowPipeline;

use Cache, Log, Redis;
use App\{Like, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\FollowPipeline\FollowDiscover;

class FollowPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $follower;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($follower)
    {
        $this->follower = $follower;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follower = $this->follower;
        $actor = $follower->actor;
        $target = $follower->target;

        try {

            $notification = new Notification;
            $notification->profile_id = $target->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'follow';
            $notification->message = $follower->toText();
            $notification->rendered = $follower->toHtml();
            $notification->save();

            Cache::forever('notification.' . $notification->id, $notification);
            
            $redis = Redis::connection();

            $nkey = config('cache.prefix').':user.' . $target->id . '.notifications';
            $redis->lpush($nkey, $notification->id);

        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
