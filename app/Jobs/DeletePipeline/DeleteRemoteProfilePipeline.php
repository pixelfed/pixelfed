<?php

namespace App\Jobs\DeletePipeline;

use App\Avatar;
use App\DirectMessage;
use App\Follower;
use App\FollowRequest;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Like;
use App\Media;
use App\MediaTag;
use App\Mention;
use App\Models\Conversation;
use App\Models\Poll;
use App\Models\PollVote;
use App\Notification;
use App\Profile;
use App\Report;
use App\Services\AccountService;
use App\Status;
use App\Story;
use App\StoryView;
use App\UserFilter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteRemoteProfilePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    public $deleteWhenMissingModels = true;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function handle()
    {
        $profile = $this->profile;
        $pid = $profile->id;

        if ($profile->domain == null || $profile->private_key) {
            return;
        }

        $profile->status = 'delete';
        $profile->save();

        AccountService::del($pid);

        // Delete statuses
        Status::whereProfileId($pid)
            ->chunk(50, function ($statuses) {
                foreach ($statuses as $status) {
                    RemoteStatusDelete::dispatch($status)->onQueue('delete');
                }
            });

        // Delete Poll Votes
        PollVote::whereProfileId($pid)->delete();

        // Delete Polls
        Poll::whereProfileId($pid)->delete();

        // Delete Avatar
        $profile->avatar->forceDelete();

        // Delete media tags
        MediaTag::whereProfileId($pid)->delete();

        // Delete DMs
        DirectMessage::whereFromId($pid)->orWhere('to_id', $pid)->delete();
        Conversation::whereFromId($pid)->orWhere('to_id', $pid)->delete();

        // Delete FollowRequests
        FollowRequest::whereFollowingId($pid)
            ->orWhere('follower_id', $pid)
            ->delete();

        // Delete relationships
        Follower::whereProfileId($pid)
            ->orWhere('following_id', $pid)
            ->delete();

        // Delete likes
        Like::whereProfileId($pid)->forceDelete();

        // Delete Story Views + Stories
        StoryView::whereProfileId($pid)->delete();
        $stories = Story::whereProfileId($pid)->get();
        foreach ($stories as $story) {
            $path = storage_path('app/'.$story->path);
            if (is_file($path)) {
                unlink($path);
            }
            $story->forceDelete();
        }

        // Delete mutes/blocks
        UserFilter::whereFilterableType('App\Profile')->whereFilterableId($pid)->delete();

        // Delete mentions
        Mention::whereProfileId($pid)->forceDelete();

        // Delete notifications
        Notification::whereProfileId($pid)
            ->orWhere('actor_id', $pid)
            ->chunk(50, function ($notifications) {
                foreach ($notifications as $n) {
                    $n->forceDelete();
                }
            });

        // Delete reports
        Report::whereProfileId($profile->id)->orWhere('reported_profile_id')->forceDelete();

        // Delete profile
        Profile::findOrFail($profile->id)->delete();

        return 1;
    }
}
