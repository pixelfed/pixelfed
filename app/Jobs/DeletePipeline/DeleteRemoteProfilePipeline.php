<?php

namespace App\Jobs\DeletePipeline;

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Avatar;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Like;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Status;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\UserFilter;
use App\Services\AccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Verify profile exists
        if (! $profile) {
            Log::info('DeleteRemoteProfilePipeline: Profile no longer exists, skipping job');

            return;
        }

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
        Story::whereProfileId($pid)->cursor()->each(function ($story) {
            $path = storage_path('app/'.$story->path);
            if (is_file($path)) {
                unlink($path);
            }
            $story->forceDelete();
        });

        // Delete mutes/blocks
        UserFilter::whereFilterableType(Profile::class)->whereFilterableId($pid)->delete();

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
        Report::whereProfileId($pid)->orWhere('reported_profile_id', $pid)->forceDelete();

        // Delete profile
        Profile::findOrFail($profile->id)->delete();

        return 1;
    }
}
