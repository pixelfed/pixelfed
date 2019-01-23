<?php

namespace App\Jobs\SharePipeline;

use App\Status;
use App\Notification;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Redis;

class SharePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $actor = $status->profile;
        $target = $status->parent()->profile;

        if ($status->uri !== null) {
            // Ignore notifications to remote statuses
            return;
        }

        $exists = Notification::whereProfileId($target->id)
                  ->whereActorId($status->profile_id)
                  ->whereAction('share')
                  ->whereItemId($status->reblog_of_id)
                  ->whereItemType('App\Status')
                  ->count();

        if ($target->id === $status->profile_id || $exists !== 0) {
            return true;
        }

        try {
            $notification = new Notification;
            $notification->profile_id = $target->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'share';
            $notification->message = $status->shareToText();
            $notification->rendered = $status->shareToHtml();
            $notification->item_id = $status->id;
            $notification->item_type = "App\Status";
            $notification->save();

            Cache::forever('notification.'.$notification->id, $notification);

            $redis = Redis::connection();
            $key = config('cache.prefix').':user.'.$status->profile_id.'.notifications';
            $redis->lpush($key, $notification->id);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
