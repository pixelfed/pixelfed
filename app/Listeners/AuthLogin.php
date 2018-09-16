<?php

namespace App\Listeners;

use App\User;
use App\UserSetting;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuthLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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
            $settings = new UserSetting();
            $settings->user_id = $user->id;
            $settings->save();
        }
    }
}
