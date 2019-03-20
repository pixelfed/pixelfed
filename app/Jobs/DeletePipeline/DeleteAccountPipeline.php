<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use App\{
    AccountLog,
    Activity,
    Avatar,
    Bookmark,
    Collection,
    DirectMessage,
    EmailVerification,
    Follower,
    FollowRequest,
    Hashtag,
    Like,
    Media,
    Mention,
    Notification,
    Profile,
    Report,
    ReportComment,
    ReportLog,
    StatusHashtag,
    Status,
    User,
    UserDevice,
    UserFilter,
    UserSetting,
};

class DeleteAccountPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;
        DB::transaction(function() use ($user) {
            AccountLog::chunk(200, function($logs) use ($user) {
                foreach($logs as $log) {
                    if($log->user_id == $user->id) {
                        $log->forceDelete();
                    }
                }
            });
        });

        DB::transaction(function() use ($user) {
            if($user->profile) {
                $avatar = $user->profile->avatar;

                if(is_file($avatar->media_path)) {
                    unlink($avatar->media_path);
                }

                if(is_file($avatar->thumb_path)) {
                    unlink($avatar->thumb_path);
                }
                $avatar->forceDelete();
            }

            Bookmark::whereProfileId($user->profile->id)->forceDelete();

            EmailVerification::whereUserId($user->id)->forceDelete();

            $id = $user->profile->id;
            FollowRequest::whereFollowingId($id)->orWhere('follower_id', $id)->forceDelete();

            Follower::whereProfileId($id)->orWhere('following_id', $id)->forceDelete();

            Like::whereProfileId($id)->forceDelete();
        });

        DB::transaction(function() use ($user) {
            $medias = Media::whereUserId($user->id)->get();
            foreach($medias as $media) {
                $path = $media->media_path;
                $thumb = $media->thumbnail_path;
                if(is_file($path)) {
                    unlink($path);
                }
                if(is_file($thumb)) {
                    unlink($thumb);
                }
                $media->forceDelete();
            }
        });

        DB::transaction(function() use ($user) {
            Mention::whereProfileId($user->profile->id)->forceDelete();
            Notification::whereProfileId($user->profile->id)->orWhere('actor_id', $user->profile->id)->forceDelete();
        });

        DB::transaction(function() use ($user) {
            Status::whereProfileId($user->profile->id)->forceDelete();
            Report::whereUserId($user->id)->forceDelete();
            $this->deleteProfile($user);
        });
    }

    protected function deleteProfile($user) {
        DB::transaction(function() use ($user) {
            Profile::whereUserId($user->id)->delete();
            $this->deleteUserSettings($user);
        });
    }

    protected function deleteUserSettings($user) {

        DB::transaction(function() use ($user) {
            UserDevice::whereUserId($user->id)->forceDelete();
            UserFilter::whereUserId($user->id)->forceDelete();
            UserSetting::whereUserId($user->id)->forceDelete();
            $this->deleteUserColumns($user);
        });
    }

    protected function deleteUserColumns($user)
    {
        DB::transaction(function() use ($user) {
            $user->status = 'deleted';
            $user->name = 'deleted';
            $user->email = $user->id;
            $user->password = '';
            $user->remember_token = null;
            $user->is_admin = false;
            $user->{'2fa_enabled'} = false;
            $user->{'2fa_secret'} = null;
            $user->{'2fa_backup_codes'} = null;
            $user->{'2fa_setup_at'} = null;
            $user->save();
        });

    }
}
