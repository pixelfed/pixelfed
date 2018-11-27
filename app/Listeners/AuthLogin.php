<?php

namespace App\Listeners;

use DB, Cache;
use App\{
    Follower,
    User,
    UserFilter,
    UserSetting
};
use Illuminate\Queue\InteractsWithQueue;
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
        if (empty($user->settings)) {
            DB::transaction(function() use($user) {
                UserSetting::firstOrCreate([
                    'user_id' => $user->id
                ]);
            });
        }
        $this->warmCache($user);
    }

    public function warmCache($user)
    {
        $pid = $user->profile->id;

        Cache::remember('feature:discover:following:'.$pid, 10080, function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });

        Cache::remember("user:filter:list:$pid", 10080, function() use($pid) {
            return UserFilter::whereUserId($pid)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
        });
    }
}
