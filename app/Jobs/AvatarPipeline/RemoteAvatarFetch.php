<?php

namespace App\Jobs\AvatarPipeline;

use App\Models\Avatar;
use App\Models\Profile;
use App\Services\MediaStorageService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[DeleteWhenMissingModels]
#[Tries(1)]
#[Timeout(300)]
#[MaxExceptions(1)]
class RemoteAvatarFetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;

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
            $avatar->save();
        }

        if ($avatar->media_path == null && $avatar->remote_url == null) {
            $avatar->media_path = 'public/avatars/default.jpg';
            $avatar->is_remote = true;
            $avatar->save();
        }

        $person = Helpers::fetchFromUrl($profile->remote_url);

        if (! $person || ! isset($person['@context'])) {
            return 1;
        }

        if (! isset($person['icon']) ||
            ! isset($person['icon']['type']) ||
            ! isset($person['icon']['url'])
        ) {
            return 1;
        }

        if ($person['icon']['type'] !== 'Image') {
            return 1;
        }

        if (! Helpers::validateUrl($person['icon']['url'])) {
            return 1;
        }

        $icon = $person['icon'];

        $avatar->remote_url = $icon['url'];
        $avatar->save();

        MediaStorageService::avatar($avatar, (bool) config_cache('pixelfed.cloud_storage') == false, true);

        return 1;
    }
}
