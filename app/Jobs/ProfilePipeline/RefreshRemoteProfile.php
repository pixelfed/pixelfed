<?php

namespace App\Jobs\ProfilePipeline;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RefreshRemoteProfile implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;

    public $timeout = 60;

    public $uniqueFor = 3600;

    public function __construct(public int $profileId) {}

    public function uniqueId(): string
    {
        return (string) $this->profileId;
    }

    public function handle(): void
    {
        $profile = Profile::find($this->profileId);

        if (! $profile || $profile->domain === null || $profile->private_key || ! $profile->remote_url) {
            return;
        }

        if (! Helpers::needsFetch($profile)) {
            return;
        }

        Helpers::profileUpdateOrCreate($profile->remote_url);
    }
}
