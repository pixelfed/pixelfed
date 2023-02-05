<?php

namespace App\Jobs\StatusPipeline;

use App\Notification;
use App\Status;
use Cache;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\NotificationService;
use App\Services\StatusService;

class StatusReplyPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $status;

	/**
	 * Delete the job if its models no longer exist.
	 *
	 * @var bool
	 */
	public $deleteWhenMissingModels = true;

	public $timeout = 60;
	public $tries = 2;

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
		$reply = Status::find($status->in_reply_to_id);

		if(!$actor || !$reply) {
			return 1;
		}

		$target = $reply->profile;

		$exists = Notification::whereProfileId($target->id)
                  ->whereActorId($actor->id)
                  ->whereIn('action', ['mention', 'comment'])
                  ->whereItemId($status->id)
                  ->whereItemType('App\Status')
                  ->count();

        if ($actor->id === $target || $exists !== 0) {
            return 1;
        }

        if(config('database.default') === 'mysql') {
            $count = DB::select(DB::raw("select id, in_reply_to_id from statuses, (select @pv := :kid) initialisation where id > @pv and find_in_set(in_reply_to_id, @pv) > 0 and @pv := concat(@pv, ',', id)"), [ 'kid' => $reply->id]);
            $reply->reply_count = count($count);
            $reply->save();
        } else {
            $reply->reply_count = $reply->reply_count + 1;
            $reply->save();
        }

        StatusService::del($reply->id);
        StatusService::del($status->id);
        Cache::forget('status:replies:all:' . $reply->id);
        Cache::forget('status:replies:all:' . $status->id);

        DB::transaction(function() use($target, $actor, $status) {
            $notification = new Notification();
            $notification->profile_id = $target->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'comment';
            $notification->message = $status->replyToText();
            $notification->rendered = $status->replyToHtml();
            $notification->item_id = $status->id;
            $notification->item_type = "App\Status";
            $notification->save();

            NotificationService::setNotification($notification);
            NotificationService::set($notification->profile_id, $notification->id);
        });

        if($exists = Cache::get('status:replies:all:' . $reply->id)) {
        	if($exists && $exists->count() == 3) {
        	} else {
        		Cache::forget('status:replies:all:' . $reply->id);
        	}
        } else {
        	Cache::forget('status:replies:all:' . $reply->id);
        }

        return 1;
	}

}
