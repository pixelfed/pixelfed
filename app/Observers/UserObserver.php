<?php

namespace App\Observers;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\DefaultDomainBlock;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Models\UserSetting;
use App\Services\FollowerService;
use Illuminate\Support\Facades\DB;

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

        // Already linked: nothing to do.
        if ($user->profile_id && $user->profile) {
            $this->createSettingsIfMissing($user);

            return;
        }

        // Recover from a partial-failure state: a profile for this user exists
        // (its own row was created) but users.profile_id was never set. Adopt
        // it instead of bailing out, which previously left the user stuck with
        // a null profile_id forever.
        $existing = Profile::whereUserId($user->id)->first();

        if ($existing) {
            DB::transaction(function () use ($user, $existing) {
                $fresh = User::findOrFail($user->id);
                $fresh->profile_id = $existing->id;
                $fresh->save();
            });
            $user->profile_id = $existing->id;
            $this->createSettingsIfMissing($user);

            return;
        }

        if (empty($user->profile)) {
            // Create the profile AND link it to the user in a single
            // transaction so a failure can't leave users.profile_id null while
            // the profile row exists.
            $profile = DB::transaction(function () use ($user) {
                $profile = new Profile;
                $profile->user_id = $user->id;
                $profile->username = $user->username;
                $profile->name = $user->name;
                $pkiConfig = [
                    'digest_alg' => 'sha512',
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

                $fresh = User::findOrFail($user->id);
                $fresh->profile_id = $profile->id;
                $fresh->save();

                return $profile;
            });

            $user->profile_id = $profile->id;

            CreateAvatar::dispatch($profile);

            if ((bool) config_cache('account.autofollow') === true) {
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

        $this->createSettingsIfMissing($user);
    }

    protected function createSettingsIfMissing($user): void
    {
        if (empty($user->settings)) {
            DB::transaction(function () use ($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id,
                ]);
            });
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
