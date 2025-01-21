<?php

namespace App\Jobs\AdminPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Avatar;
use App\Follower;
use App\Instance;
use App\Media;
use App\Profile;
use App\Status;
use Cache;
use Storage;
use Purify;
use App\Services\ActivityPubFetchService;
use App\Services\AccountService;
use App\Services\MediaStorageService;
use App\Services\StatusService;
use App\Jobs\StatusPipeline\RemoteStatusDelete;

class AdminProfileActionPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $action;
    protected $profile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($profile, $action)
    {
        $this->profile = $profile;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;
        $action = $this->action;

        switch($action) {
            case 'mark-all-cw':
                return $this->markAllPostsWithContentWarnings();
            break;
            case 'unlist-all':
                return $this->unlistAllPosts();
            break;
            case 'purge':
                return $this->purgeAllPosts();
            break;
            case 'refetch':
                return $this->refetchAllPosts();
            break;
        }
    }

    protected function markAllPostsWithContentWarnings()
    {
        $profile = $this->profile;

        foreach(Status::whereProfileId($profile->id)->lazyById(10, 'id') as $status) {
            if($status->scope == 'direct') {
                continue;
            }
            $status->is_nsfw = true;
            $status->save();
            StatusService::del($status->id);
        }
    }

    protected function unlistAllPosts()
    {
        $profile = $this->profile;

        foreach(Status::whereProfileId($profile->id)->lazyById(10, 'id') as $status) {
            if($status->scope != 'public') {
                continue;
            }
            $status->scope = 'unlisted';
            $status->visibility = 'unlisted';
            $status->save();
            StatusService::del($status->id);
        }
    }

    protected function purgeAllPosts()
    {
        $profile = $this->profile;

        foreach(Status::withTrashed()->whereProfileId($profile->id)->lazyById(10, 'id') as $status) {
            RemoteStatusDelete::dispatch($status)->onQueue('delete');
        }
    }

    protected function refetchAllPosts()
    {
        $profile = $this->profile;
        $res = ActivityPubFetchService::get($profile->remote_url, false);
        if(!$res) {
            return;
        }
        $res = json_decode($res, true);
        $profile->following_count = Follower::whereProfileId($profile->id)->count();
        $profile->followers_count = Follower::whereFollowingId($profile->id)->count();
        $profile->name = isset($res['name']) ? Purify::clean($res['name']) : $profile->username;
        $profile->bio = isset($res['summary']) ? Purify::clean($res['summary']) : null;
        if(isset($res['publicKey'])) {
            $profile->public_key = $res['publicKey']['publicKeyPem'];
        }
        if(
            isset($res['icon']) &&
            isset(
                $res['icon']['type'],
                $res['icon']['mediaType'],
                $res['icon']['url']) && $res['icon']['type'] == 'Image'
        ) {
            if(in_array($res['icon']['mediaType'], ['image/jpeg', 'image/png'])) {
                $profile->avatar->remote_url = $res['icon']['url'];
                $profile->push();
                MediaStorageService::avatar($profile->avatar);
            }
        }
        $profile->save();
        AccountService::del($profile->id);
    }
}
