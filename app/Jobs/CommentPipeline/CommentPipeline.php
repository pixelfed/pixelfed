<?php

namespace App\Jobs\CommentPipeline;

use App\Notification;
use App\Status;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Redis;

class CommentPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $comment;

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
    public function __construct(Status $status, Status $comment)
    {
        $this->status = $status;
        $this->comment = $comment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $comment = $this->comment;

        $target = $status->profile;
        $actor = $comment->profile;

        if ($actor->id === $target->id) {
            return true;
        }

        try {
            $notification = new Notification();
            $notification->profile_id = $target->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'comment';
            $notification->message = $comment->replyToText();
            $notification->rendered = $comment->replyToHtml();
            $notification->item_id = $comment->id;
            $notification->item_type = "App\Status";
            $notification->save();

            Cache::forever('notification.'.$notification->id, $notification);

            $redis = Redis::connection();

            $nkey = config('cache.prefix').':user.'.$target->id.'.notifications';
            $redis->lpush($nkey, $notification->id);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
