<?php

namespace App\Jobs\CommentPipeline;

use App\Models\Profile;
use App\Models\Status;
use App\Models\UserFilter;
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
#[Timeout(5)]
#[Tries(1)]
class CommentPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    protected $comment;

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

        // Verify status and comment exists
        if (! $status) {
            Log::info('CommentPipeline: Status no longer exists, skipping job');

            return;
        }
        if (! $comment) {
            Log::info('CommentPipeline: Comment no longer exists, skipping job');

            return;
        }

        $target = $status->profile;
        $actor = $comment->profile;

        // Verify target and actor profiles exist
        if (! $target) {
            Log::info("CommentPipeline: Target profile no longer exists for status {$status->id}, skipping job");

            return;
        }
        if (! $actor) {
            Log::info("CommentPipeline: Actor profile no longer exists for comment {$comment->id}, skipping job");

            return;
        }

        if (config('database.default') === 'mysql') {
            // todo: refactor
            // $exp = DB::raw("select id, in_reply_to_id from statuses, (select @pv := :kid) initialisation where id > @pv and find_in_set(in_reply_to_id, @pv) > 0 and @pv := concat(@pv, ',', id)");
            // $expQuery = $exp->getValue(DB::connection()->getQueryGrammar());
            // $count = DB::select($expQuery, [ 'kid' => $status->id ]);
            // $status->reply_count = count($count);
            $status->reply_count = $status->reply_count + 1;
            $status->save();
        } else {
            $status->reply_count = $status->reply_count + 1;
            $status->save();
        }

        StatusService::del($comment->id);
        StatusService::del($status->id);
        Cache::forget('status:replies:all:'.$comment->id);
        Cache::forget('status:replies:all:'.$status->id);

        if ($actor->id === $target->id || $status->comments_disabled == true) {
            return true;
        }

        $filtered = UserFilter::whereUserId($target->id)
            ->whereFilterableType(Profile::class)
            ->whereIn('filter_type', ['mute', 'block'])
            ->whereFilterableId($actor->id)
            ->exists();

        if ($filtered == true) {
            return;
        }

        if ($target->user_id && $target->domain === null) {
            DB::transaction(function () use ($target, $actor, $comment) {
                NotificationService::createNotification($target->id, $actor->id, 'comment', $comment->id, Status::class);
                StatusService::del($comment->id);
            });
        }

        if ($exists = Cache::get('status:replies:all:'.$status->id)) {
            if ($exists && $exists->count() == 3) {
            } else {
                Cache::forget('status:replies:all:'.$status->id);
            }
        } else {
            Cache::forget('status:replies:all:'.$status->id);
        }
    }
}
