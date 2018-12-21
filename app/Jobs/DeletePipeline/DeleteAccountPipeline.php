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

            Mention::whereProfileId($user->profile->id)->forceDelete();

            Notification::whereProfileId($id)->orWhere('actor_id', $id)->forceDelete();

            Status::whereProfileId($user->profile->id)->forceDelete();

            Report::whereUserId($user->id)->forceDelete();
            $this->deleteProfile($user);
        });
    }

    public function deleteProfile($user) {
        DB::transaction(function() use ($user) {
            Profile::whereUserId($user->id)->delete();
            $this->deleteUser($user);
        });
    }

    public function deleteUser($user) {

        DB::transaction(function() use ($user) {
            UserFilter::whereUserId($user->id)->forceDelete();
            UserSetting::whereUserId($user->id)->forceDelete();
            $user->forceDelete();
        });
    }
}
