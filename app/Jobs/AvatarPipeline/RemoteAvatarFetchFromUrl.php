<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use App\Services\AccountService;
use App\Services\MediaStorageService;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoteAvatarFetchFromUrl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    protected $url;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    public $timeout = 300;

    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Profile $profile, $url)
    {
        $this->profile = $profile;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;

        Cache::forget('avatar:'.$profile->id);
        AccountService::del($profile->id);

        if ((bool) config_cache('pixelfed.cloud_storage') == false && (bool) config_cache('federation.avatars.store_local') == false) {
            return 1;
        }

        if ($profile->domain == null || $profile->private_key) {
            return 1;
        }

        $avatar = Avatar::whereProfileId($profile->id)->first();

        if (! $avatar) {
            $avatar = new Avatar;
            $avatar->profile_id = $profile->id;
            $avatar->is_remote = true;
            $avatar->remote_url = $this->url;
            $avatar->save();
        } else {
            $avatar->remote_url = $this->url;
            $avatar->is_remote = true;
            $avatar->save();
        }

        MediaStorageService::avatar($avatar, (bool) config_cache('pixelfed.cloud_storage') == false, true);

        return 1;
    }
}
