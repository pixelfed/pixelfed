<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Notification;
use App\Models\Status;
use App\Services\NotificationService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
#[Timeout(60)]
#[Tries(2)]
class StatusReplyPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

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

        // Verify status exists
        if (! $status) {
            Log::info('StatusReplyPipeline: Status no longer exists, skipping job');

            return 1;
        }

        // Verify status is a reply
        if (! $status->in_reply_to_id) {
            Log::info("StatusReplyPipeline: Status {$status->id} is not a reply, skipping job");

            return 1;
        }

        $actor = $status->profile;
        if (! $actor) {
            Log::info("StatusReplyPipeline: Actor profile no longer exists for status {$status->id}, skipping job");

            return 1;
        }

        $reply = Status::find($status->in_reply_to_id);
        if (! $reply) {
            Log::info("StatusReplyPipeline: Reply status {$status->in_reply_to_id} no longer exists for status {$status->id}, skipping job");

            return 1;
        }

        $target = $reply->profile;
        if (! $target) {
            Log::info("StatusReplyPipeline: Target profile no longer exists for reply {$reply->id}, skipping job");

            return 1;
        }

        $exists = Notification::whereProfileId($target->id)
            ->whereActorId($actor->id)
            ->whereIn('action', ['mention', 'comment'])
            ->whereItemId($status->id)
            ->whereItemType(Status::class)
            ->count();

        if ($actor->id === $target || $exists !== 0) {
            return 1;
        }

        if (config('database.default') === 'mysql') {
            // todo: refactor
            // $exp = DB::raw("select id, in_reply_to_id from statuses, (select @pv := :kid) initialisation where id > @pv and find_in_set(in_reply_to_id, @pv) > 0 and @pv := concat(@pv, ',', id)");
            // $expQuery = $exp->getValue(DB::connection()->getQueryGrammar());
            // $count = DB::select($expQuery, [ 'kid' => $reply->id ]);
            // $reply->reply_count = count($count);
            $reply->reply_count = $reply->reply_count + 1;
            $reply->save();
        } else {
            $reply->reply_count = $reply->reply_count + 1;
            $reply->save();
        }

        StatusService::del($reply->id);
        StatusService::del($status->id);
        Cache::forget('status:replies:all:'.$reply->id);
        Cache::forget('status:replies:all:'.$status->id);

        if ($target->user_id && $target->domain === null) {
            DB::transaction(function () use ($target, $actor, $status) {
                NotificationService::createNotification($target->id, $actor->id, 'comment', $status->id, Status::class);
            });
        }

        if ($exists = Cache::get('status:replies:all:'.$reply->id)) {
            if ($exists && $exists->count() == 3) {
            } else {
                Cache::forget('status:replies:all:'.$reply->id);
            }
        } else {
            Cache::forget('status:replies:all:'.$reply->id);
        }

        return 1;
    }
}
