<?php

namespace App\Observers;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Follower;
use App\Profile;
use App\User;
use App\UserSetting;
use App\Jobs\FollowPipeline\FollowPipeline;
use DB;
use App\Services\FollowerService;

class UserObserver
{
	/**
	 * Listen to the User created event.
	 *
	 * @param \App\User $user
	 *
	 * @return void
	 */
	public function saved(User $user)
	{
		if($user->status == 'deleted') {
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
}
