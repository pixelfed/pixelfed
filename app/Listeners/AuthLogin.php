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
}
