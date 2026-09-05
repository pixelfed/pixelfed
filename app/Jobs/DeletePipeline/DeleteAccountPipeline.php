<?php

namespace App\Jobs\DeletePipeline;

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\AccountInterstitial;
use App\Models\AccountLog;
use App\Models\Bookmark;
use App\Models\Collection;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CustomFilter;
use App\Models\DirectMessage;
use App\Models\EmailVerification;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\HashtagFollow;
use App\Models\ImportPost;
use App\Models\Like;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\OauthClient;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Portfolio;
use App\Models\Profile;
use App\Models\ProfileAlias;
use App\Models\ProfileMigration;
use App\Models\ProfileSponsor;
use App\Models\RemoteAuth;
use App\Models\RemoteReport;
use App\Models\Report;
use App\Models\Status;
use App\Models\StatusArchived;
use App\Models\StatusHashtag;
use App\Models\StatusView;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserFilter;
use App\Models\UserPronoun;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\PublicTimelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteAccountPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public $timeout = 1800;

    public $tries = 3;

    public $maxExceptions = 1;

    public $deleteWhenMissingModels = true;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $user = $this->user;

        // Verify user exists
        if (! $user) {
            Log::info('DeleteAccountPipeline: User no longer exists, skipping job');

            return;
        }

        // Verify user has a profile
        if (! $user->profile_id) {
            Log::info("DeleteAccountPipeline: User {$user->id} has no profile_id, skipping job");

            return;
        }

        $profile = $user->profile;
        $id = $user->profile_id;
        $cloudStorageEnabled = (bool) config_cache('pixelfed.cloud_storage');
        $cloudDisk = config('filesystems.cloud');

        if ($user && $user->id && is_numeric($user->id)) {
            $directory = 'imports/'.$user->id;

            if (Storage::exists($directory)) {
                Storage::deleteDirectory($directory);
            }
        }

        if ($id && is_numeric($id)) {
            $mediaDir = 'public/m/_v2/'.$id;

            if (Storage::exists($mediaDir)) {
                Storage::deleteDirectory($mediaDir);
            }

            if ($cloudStorageEnabled && $cloudDisk) {
                if (Storage::disk($cloudDisk)->exists($mediaDir)) {
                    Storage::disk($cloudDisk)->deleteDirectory($mediaDir);
                }
            }
        }

        Status::whereProfileId($id)->chunk(50, function ($statuses) {
            foreach ($statuses as $status) {
                StatusDelete::dispatch($status);
            }
        });

        CustomFilter::whereProfileId($id)->delete();

        StatusView::whereProfileId($id)->delete();

        ProfileAlias::whereProfileId($id)->delete();

        ProfileMigration::whereProfileId($id)->delete();

        RemoteReport::whereAccountId($id)->delete();

        RemoteAuth::whereUserId($user->id)->delete();

        AccountLog::whereItemType(User::class)->whereItemId($user->id)->forceDelete();

        AccountInterstitial::whereUserId($user->id)->delete();

        $profile->avatar->forceDelete();

        PollVote::whereProfileId($id)->delete();

        Poll::whereProfileId($id)->delete();

        Portfolio::whereProfileId($id)->delete();

        ImportPost::whereProfileId($id)->delete();

        MediaTag::whereProfileId($id)->delete();
        Bookmark::whereProfileId($id)->forceDelete();
        EmailVerification::whereUserId($user->id)->forceDelete();
        StatusHashtag::whereProfileId($id)->delete();
        DirectMessage::whereFromId($id)->orWhere('to_id', $id)->delete();
        Conversation::whereFromId($id)->orWhere('to_id', $id)->delete();
        StatusArchived::whereProfileId($id)->delete();
        UserPronoun::whereProfileId($id)->delete();
        FollowRequest::whereFollowingId($id)
            ->orWhere('follower_id', $id)
            ->forceDelete();
        Follower::whereProfileId($id)
            ->orWhere('following_id', $id)
            ->each(function ($follow) {
                FollowerService::remove($follow->profile_id, $follow->following_id);
                $follow->delete();
            });
        FollowerService::delCache($id);
        Like::whereProfileId($id)->forceDelete();
        Mention::whereProfileId($id)->forceDelete();

        StoryView::whereProfileId($id)->delete();
        Story::whereProfileId($id)->cursor()->each(function ($story) {
            $path = storage_path('app/'.$story->path);
            if (is_file($path)) {
                unlink($path);
            }
            $story->forceDelete();
        });

        UserDevice::whereUserId($user->id)->forceDelete();
        UserFilter::whereUserId($user->id)->forceDelete();
        UserSetting::whereUserId($user->id)->forceDelete();

        Mention::whereProfileId($id)->forceDelete();
        Notification::whereProfileId($id)
            ->orWhere('actor_id', $id)
            ->forceDelete();

        Collection::whereProfileId($id)->cursor()->each(function ($collection) {
            $collection->items()->delete();
            $collection->delete();
        });
        Contact::whereUserId($user->id)->delete();
        HashtagFollow::whereUserId($user->id)->delete();
        OauthClient::whereUserId($user->id)->delete();
        DB::table('oauth_access_tokens')->whereUserId($user->id)->delete();
        DB::table('oauth_auth_codes')->whereUserId($user->id)->delete();
        ProfileSponsor::whereProfileId($id)->delete();

        Report::whereUserId($user->id)->forceDelete();
        PublicTimelineService::warmCache(true, 400);
        $this->deleteUserColumns($user);
        AccountService::del($user->profile_id);
        Profile::whereUserId($user->id)->delete();
    }

    protected function deleteUserColumns($user)
    {
        DB::transaction(function () use ($user) {
            $user->status = 'deleted';
            $user->name = 'deleted';
            $user->email = $user->id;
            $user->password = '';
            $user->remember_token = null;
            $user->is_admin = false;
            $user->expo_token = null;
            $user->{'2fa_enabled'} = false;
            $user->{'2fa_secret'} = null;
            $user->{'2fa_backup_codes'} = null;
            $user->{'2fa_setup_at'} = null;
            $user->save();
        });
    }
}
