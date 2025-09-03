<?php

namespace App\Jobs\DeletePipeline;

use App\AccountInterstitial;
use App\AccountLog;
use App\Bookmark;
use App\Collection;
use App\Contact;
use App\DirectMessage;
use App\EmailVerification;
use App\Follower;
use App\FollowRequest;
use App\HashtagFollow;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Like;
use App\MediaTag;
use App\Mention;
use App\Models\Conversation;
use App\Models\CustomFilter;
use App\Models\ImportPost;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Portfolio;
use App\Models\ProfileAlias;
use App\Models\ProfileMigration;
use App\Models\RemoteAuth;
use App\Models\RemoteReport;
use App\Models\UserPronoun;
use App\Notification;
use App\OauthClient;
use App\Profile;
use App\ProfileSponsor;
use App\Report;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\PublicTimelineService;
use App\Status;
use App\StatusArchived;
use App\StatusHashtag;
use App\StatusView;
use App\Story;
use App\StoryView;
use App\User;
use App\UserDevice;
use App\UserFilter;
use App\UserSetting;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

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

        DB::table('user_oidc_mappings')->whereUserId($user->id)->delete();

        CustomFilter::whereProfileId($id)->delete();

        StatusView::whereProfileId($id)->delete();

        ProfileAlias::whereProfileId($id)->delete();

        ProfileMigration::whereProfileId($id)->delete();

        RemoteReport::whereAccountId($id)->delete();

        RemoteAuth::whereUserId($user->id)->delete();

        AccountLog::whereItemType('App\User')->whereItemId($user->id)->forceDelete();

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
        $stories = Story::whereProfileId($id)->get();
        foreach ($stories as $story) {
            $path = storage_path('app/'.$story->path);
            if (is_file($path)) {
                unlink($path);
            }
            $story->forceDelete();
        }

        UserDevice::whereUserId($user->id)->forceDelete();
        UserFilter::whereUserId($user->id)->forceDelete();
        UserSetting::whereUserId($user->id)->forceDelete();

        Mention::whereProfileId($id)->forceDelete();
        Notification::whereProfileId($id)
            ->orWhere('actor_id', $id)
            ->forceDelete();

        $collections = Collection::whereProfileId($id)->get();
        foreach ($collections as $collection) {
            $collection->items()->delete();
            $collection->delete();
        }
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
