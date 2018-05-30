<?php

namespace App\Jobs\LikePipeline;

use Cache, Log, Redis;
use App\{Like, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LikePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $like = $this->like;

        $status = $this->like->status;
        $actor = $this->like->actor;

        if($actor->id === $status->profile_id) {
            return true;
        }

        try {

            $notification = new Notification;
            $notification->profile_id = $status->profile_id;
            $notification->actor_id = $actor->id;
            $notification->action = 'like';
            $notification->message = $like->toText();
            $notification->rendered = $like->toHtml();
            $notification->save();

            Cache::forever('notification.' . $notification->id, $notification);
            
            $redis = Redis::connection();
            $key = config('cache.prefix').':user.' . $status->profile_id . '.notifications';
            $redis->lpush($key, $notification->id);

        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
