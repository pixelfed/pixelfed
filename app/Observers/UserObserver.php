<?php

namespace App\Observers;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Follower;
use App\Profile;
use App\User;
use App\UserSetting;
use App\Services\UserFilterService;
use App\Models\DefaultDomainBlock;
use App\Models\UserDomainBlock;
use App\Jobs\FollowPipeline\FollowPipeline;
use DB;
use App\Services\FollowerService;

class UserObserver
{
    /**
     * Handle the notification "created" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function created(User $user): void
    {
        $this->handleUser($user);
    }

    /**
     * Listen to the User saved event.
     *
     * @param \App\User $user
     *
     * @return void
     */
    public function saved(User $user)
    {
        $this->handleUser($user);
    }

    /**
     * Listen to the User updated event.
     *
     * @param \App\User $user
     *
     * @return void
     */
    public function updated(User $user): void
    {
        $this->handleUser($user);
        if($user->profile) {
            $this->applyDefaultDomainBlocks($user);
        }
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param  \App\User $user
     * @return void
     */
    public function deleted(User $user)
    {
        FollowerService::delCache($user->profile_id);
    }

    protected function handleUser($user)
    {
        if(in_array($user->status, ['deleted', 'delete'])) {
            return;
        }

        if(Profile::whereUsername($user->username)->exists()) {
            return;
        }

        if (empty($user->profile)) {
            $profile = DB::transaction(function() use($user) {
                $profile = new Profile();
                $profile->user_id = $user->id;
                $profile->username = $user->username;
                $profile->name = $user->name;
                $pkiConfig = [
                    'digest_alg'       => 'sha512',
                    'private_key_bits' => 2048,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA,
                ];
                $pki = openssl_pkey_new($pkiConfig);
                openssl_pkey_export($pki, $pki_private);
                $pki_public = openssl_pkey_get_details($pki);
                $pki_public = $pki_public['key'];

                $profile->private_key = $pki_private;
                $profile->public_key = $pki_public;
                $profile->save();
                $this->applyDefaultDomainBlocks($user);
                return $profile;
            });


            DB::transaction(function() use($user, $profile) {
                $user = User::findOrFail($user->id);
                $user->profile_id = $profile->id;
                $user->save();

                CreateAvatar::dispatch($profile);
            });

            if(config_cache('account.autofollow') == true) {
                $names = config_cache('account.autofollow_usernames');
                $names = explode(',', $names);

                if(!$names || !last($names)) {
                    return;
                }

                $profiles = Profile::whereIn('username', $names)->get();

                if($profiles) {
                    foreach($profiles as $p) {
                        $follower = new Follower;
                        $follower->profile_id = $profile->id;
                        $follower->following_id = $p->id;
                        $follower->save();

                        FollowPipeline::dispatch($follower);
                    }
                }
            }
        }

        if (empty($user->settings)) {
            DB::transaction(function() use($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id
                ]);
            });
        }
    }

    protected function applyDefaultDomainBlocks($user)
    {
        if($user->profile_id == null) {
            return;
        }
        $defaultDomainBlocks = DefaultDomainBlock::pluck('domain')->toArray();

        if(!$defaultDomainBlocks || !count($defaultDomainBlocks)) {
            return;
        }

        foreach($defaultDomainBlocks as $domain) {
            UserDomainBlock::updateOrCreate([
                'profile_id' => $user->profile_id,
                'domain' => strtolower(trim($domain))
            ]);
        }
    }
}
