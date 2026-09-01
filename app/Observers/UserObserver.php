<?php

namespace App\Observers;

use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\DefaultDomainBlock;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Services\Account\AccountInitializer;
use App\Services\FollowerService;

class UserObserver
{
    /**
     * Handle the notification "created" event.
     */
    public function created(User $user): void
    {
        $this->handleUser($user);
    }

    /**
     * Listen to the User saved event.
     *
     *
     * @return void
     */
    public function saved(User $user)
    {
        $this->handleUser($user);
    }

    /**
     * Listen to the User updated event.
     */
    public function updated(User $user): void
    {
        $this->handleUser($user);
        if ($user->profile) {
            $this->applyDefaultDomainBlocks($user);
        }
    }

    /**
     * Handle the user "deleted" event.
     *
     * @return void
     */
    public function deleted(User $user)
    {
        FollowerService::delCache($user->profile_id);
    }

    protected function handleUser($user)
    {
        if (in_array($user->status, ['deleted', 'delete'])) {
            return;
        }

        if (Profile::whereUsername($user->username)->exists()) {
            return;
        }

        $profile = app(AccountInitializer::class)->initialize($user);

        if ($profile->wasRecentlyCreated) {
            $this->applyDefaultDomainBlocks($user);
            if ((bool) config_cache('account.autofollow') == true) {
                $names = config_cache('account.autofollow_usernames');
                $names = explode(',', $names);

                if (! $names || ! last($names)) {
                    return;
                }

                $profiles = Profile::whereIn('username', $names)->get();

                if ($profiles) {
                    foreach ($profiles as $p) {
                        $follower = new Follower;
                        $follower->profile_id = $profile->id;
                        $follower->following_id = $p->id;
                        $follower->save();

                        FollowPipeline::dispatch($follower);
                    }
                }
            }
        }
    }

    protected function applyDefaultDomainBlocks($user)
    {
        if ($user->profile_id == null) {
            return;
        }
        $defaultDomainBlocks = DefaultDomainBlock::pluck('domain')->toArray();

        if (! $defaultDomainBlocks || ! count($defaultDomainBlocks)) {
            return;
        }

        foreach ($defaultDomainBlocks as $domain) {
            UserDomainBlock::updateOrCreate([
                'profile_id' => $user->profile_id,
                'domain' => strtolower(trim($domain)),
            ]);
        }
    }
}
