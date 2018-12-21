<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
        $this->deleteAccountLogs($user);
        $this->deleteActivities($user);
        $this->deleteAvatar($user);
        $this->deleteBookmarks($user);
        $this->deleteEmailVerification($user);
        $this->deleteFollowRequests($user);
        $this->deleteFollowers($user);
        $this->deleteLikes($user);
        $this->deleteMedia($user);
        $this->deleteMentions($user);
        $this->deleteNotifications($user);
        $this->deleteStatuses($user);
        $this->deleteReports($user);
        $this->deleteProfile($user);
        $this->deleteUser($user);

        // TODO: send Delete to every known instance sharedInbox   
    }

    public function deleteAccountLogs($user)
    {
        AccountLog::chunk(200, function($logs) use ($user) {
            foreach($logs as $log) {
                if($log->user_id == $user->id) {
                    $log->forceDelete();
                }
            }
        });
    }

    public function deleteActivities($user)
    {
        // deprecated, removed inbox activity logger
    }

    public function deleteAvatar($user)
    {
        $avatar = $user->profile->avatar;

        if(is_file($avatar->media_path)) {
            unlink($avatar->media_path);
        }

        if(is_file($avatar->thumb_path)) {
            unlink($avatar->thumb_path);
        }

        $avatar->forceDelete();
    }

    public function deleteBookmarks($user)
    {
        Bookmark::whereProfileId($user->profile->id)->forceDelete();
    }

    public function deleteEmailVerification($user)
    {
        EmailVerification::whereUserId($user->id)->forceDelete();
    }

    public function deleteFollowRequests($user)
    {
        $id = $user->profile->id;
        FollowRequest::whereFollowingId($id)->orWhere('follower_id', $id)->forceDelete();
    }

    public function deleteFollowers($user)
    {
        $id = $user->profile->id;
        Follower::whereProfileId($id)->orWhere('following_id', $id)->forceDelete();
    }

    public function deleteLikes($user)
    {
        $id = $user->profile->id;
        Like::whereProfileId($id)->forceDelete();
    }

    public function deleteMedia($user)
    {
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
    }

    public function deleteMentions($user)
    {
        Mention::whereProfileId($user->profile->id)->forceDelete();
    }

    public function deleteNotifications($user)
    {
        $id = $user->profile->id;
        Notification::whereProfileId($id)->orWhere('actor_id', $id)->forceDelete();
    }

    public function deleteStatuses($user) {
        Status::whereProfileId($user->profile->id)->forceDelete();
    }

    public function deleteProfile($user) {
        Profile::whereUserId($user->id)->delete();
    }

    public function deleteReports($user) {
        Report::whereUserId($user->id)->forceDelete();
    }

    public function deleteUser($user) {
        UserFilter::find($user->id)->forceDelete();
        UserSetting::find($user->id)->forceDelete();
        User::find($user->id)->forceDelete();
    }
}
