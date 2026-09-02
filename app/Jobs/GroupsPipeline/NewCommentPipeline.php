<?php

namespace App\Jobs\GroupsPipeline;

use App\Models\GroupComment;
use App\Models\GroupHashtag;
use App\Models\GroupPost;
use App\Models\GroupPostHashtag;
use App\Services\GroupFeedService;
use App\Services\GroupPostService;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

#[DeleteWhenMissingModels]
class NewCommentPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    protected $parent;

    protected $entities;

    protected $autolink;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($parent, GroupComment $status)
    {
        $this->parent = $parent;
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
        $parent = $this->parent;
        $profile = $this->status->profile;
        $parentClass = get_class($parent);
        $groupId = $status->group_id;
        $postId = $status->id;

        if ($parentClass === GroupPost::class) {
            $parent->reply_count = GroupPostService::getCommentCount($parent->id, false);
            $parent->save();
            GroupPostService::del($groupId, $postId);
            GroupFeedService::del($groupId, $postId);
        } elseif ($parentClass === GroupComment::class) {
            $gp = GroupPost::whereId($parent->status_id)->firstOrFail();
            if ($gp->group_id !== $status->group_id) {
                return;
            }
            $gp->reply_count = GroupPostService::getCommentCount($parent->status_id, false);
            $gp->save();
            $parent->replies_count = GroupComment::whereInReplyToId($parent->id)->count();
            $parent->save();
            GroupPostService::del($groupId, $postId);
            GroupFeedService::del($groupId, $postId);
            GroupPostService::del($groupId, $gp->id);
            GroupFeedService::del($groupId, $gp->id);
        }

        if ($profile->no_autolink == false) {
            $this->parseEntities();
        }
    }

    public function parseEntities()
    {
        $this->extractEntities();
    }

    public function extractEntities()
    {
        $this->entities = Extractor::create()->extract($this->status->caption);
        $this->autolinkStatus();
    }

    public function autolinkStatus()
    {
        $this->autolink = Autolink::create()->autolink($this->status->caption);
        $this->storeHashtags();
    }

    public function storeHashtags()
    {
        $tags = array_unique($this->entities['hashtags']);
        $status = $this->status;

        foreach ($tags as $tag) {
            if (mb_strlen($tag) > 124) {
                continue;
            }
            DB::transaction(function () use ($status, $tag) {
                $hashtag = GroupHashtag::firstOrCreate([
                    'name' => $tag,
                ]);

                GroupPostHashtag::firstOrCreate(
                    [
                        'status_id' => $status->id,
                        'group_id' => $status->group_id,
                        'hashtag_id' => $hashtag->id,
                        'profile_id' => $status->profile_id,
                        'status_visibility' => $status->visibility,
                    ]
                );
            });
        }
        $this->storeMentions();
    }

    public function storeMentions()
    {
        // todo
    }
}
