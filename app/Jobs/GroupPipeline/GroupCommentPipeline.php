<?php

namespace App\Jobs\GroupPipeline;

use App\Models\GroupPost;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GroupCommentPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    protected $comment;

    protected $groupPost;

    public function __construct(Status $status, Status $comment, $groupPost = null)
    {
        $this->status = $status;
        $this->comment = $comment;
        $this->groupPost = $groupPost;
    }

    public function handle()
    {
        if ($this->status->group_id == null || $this->comment->group_id == null) {
            return;
        }

        $this->updateParentReplyCount();
        $this->generateNotification();

        if ($this->groupPost) {
            $this->updateChildReplyCount();
        }
    }

    protected function updateParentReplyCount()
    {
        $parent = $this->status;
        $parent->reply_count = Status::whereInReplyToId($parent->id)->count();
        $parent->save();
        StatusService::del($parent->id);
    }

    protected function updateChildReplyCount()
    {
        $gp = $this->groupPost;
        if ($gp->reply_child_id) {
            $parent = GroupPost::whereStatusId($gp->reply_child_id)->first();
            if ($parent) {
                $parent->reply_count++;
                $parent->save();
            }
        }
    }

    protected function generateNotification()
    {
        $status = $this->status;
        $comment = $this->comment;

        $target = $status->profile;
        $actor = $comment->profile;

        if ($actor->id == $target->id || $status->comments_disabled == true) {
            return;
        }

        $notification = DB::transaction(function () use ($target, $actor, $comment) {
            $actorName = $actor->username;
            $actorUrl = $actor->url();
            $text = "{$actorName}  commented on your group post.";
            $html = "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> commented on your group post.";

            return NotificationService::createNotification($target->id, $actor->id, 'group:comment', $comment->id, Status::class);
        });
    }
}
