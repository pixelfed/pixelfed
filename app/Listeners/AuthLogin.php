<?php

namespace App\Listeners;

use DB, Cache;
use App\{
    Follower,
    Profile,
    User,
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

        if (empty($user->settings)) {
            DB::transaction(function() use($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id
                ]);
            });
        }
        
        if(empty($user->profile)) {
            DB::transaction(function() use($user) {
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

                CreateAvatar::dispatch($profile);
            });
        }

        if($user->status != null) {
            $profile = $user->profile;
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
}
