<?php

namespace App\Services;

use App\Jobs\ProfilePipeline\RefreshRemoteProfile;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;

class RemoteActorRefresher
{
    public const int MAX_SYNC_REFRESHES = 3;

    public const int RETRY_AFTER = 3600;

    private const string ATTEMPT_KEY = 'pf:services:remote-actor-refresher:attempt:';

    protected int $syncRefreshes = 0;

    protected array $checked = [];

    public function fresh(Profile $profile): Profile
    {
        if ($profile->domain === null || $profile->private_key || ! $profile->remote_url) {
            return $profile;
        }

        if (isset($this->checked[$profile->id])) {
            return $this->checked[$profile->id];
        }

        if (! Helpers::needsFetch($profile) || ! Cache::add(self::ATTEMPT_KEY.$profile->id, 1, self::RETRY_AFTER)) {
            return $this->checked[$profile->id] = $profile;
        }

        if ($this->syncRefreshes >= self::MAX_SYNC_REFRESHES) {
            RefreshRemoteProfile::dispatch((int) $profile->id)->onQueue('low');

            return $this->checked[$profile->id] = $profile;
        }

        $this->syncRefreshes++;

        return $this->checked[$profile->id] = $this->fetch($profile) ?? $profile;
    }

    protected function fetch(Profile $profile): ?Profile
    {
        return Helpers::profileUpdateOrCreate($profile->remote_url);
    }
}
