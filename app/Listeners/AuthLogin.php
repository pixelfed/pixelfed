<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
namespace App\Listeners;

use DB, Cache;
use App\{
    Follower,
    Profile,
    User,
    UserDevice,
    UserFilter,
    UserSetting
};
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\AvatarPipeline\CreateAvatar;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuthLogin
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;

        if(!$user) {
            return;
        }

        $this->userProfile($user);
        $this->userSettings($user);
        $this->userState($user);
        $this->userDevice($user);
        $this->userProfileId($user);
        $this->userLanguage($user);
    }

    protected function userProfile($user)
    {
        if (empty($user->profile)) {
            if($user->created_at->lt(now()->subDays(1)) && empty($user->status)) {
                $p = Profile::withTrashed()->whereUserId($user->id)->first();
                if($p) {
                    $p->restore();
                    return;
                }
            }
            DB::transaction(function() use($user) {
                $profile = Profile::firstOrCreate(['user_id' => $user->id]);
                if($profile->wasRecentlyCreated == true) {
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

                    CreateAvatar::dispatch($profile);
                }
            });

        }
    }

    protected function userSettings($user)
    {
        if (empty($user->settings)) {
            DB::transaction(function() use($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id
                ]);
            });
        }
    }

    protected function userState($user)
    {
        if($user->status != null) {
            $profile = $user->profile;
            if(!$profile) {
                return;
            }
            switch ($user->status) {
                case 'disabled':
                    $profile->status = null;
                    $user->status = null;
                    $profile->save();
                    $user->save();
                    break;

                case 'delete':
                    $profile->status = null;
                    $profile->delete_after = null;
                    $user->status = null;
                    $user->delete_after = null;
                    $profile->save();
                    $user->save();
                    break;
                
                default:
                    # code...
                    break;
            }
        }
    }

    protected function userDevice($user)
    {
        $device = DB::transaction(function() use($user) {
            return UserDevice::firstOrCreate([
                'user_id'       => $user->id,
                'ip'            => request()->ip(),
                'user_agent'    => str_limit(request()->userAgent(), 180),
            ]);
        });
    }

    protected function userProfileId($user)
    {
        if($user->profile_id == null) {
            DB::transaction(function() use($user) {
                $profile = $user->profile;
                if($profile) {
                    $user->profile_id = $profile->id;
                    $user->save();
                }
            });
        }
    }

    protected function userLanguage($user)
    {
        session()->put('locale', $user->language ?? config('app.locale'));
    }
}
