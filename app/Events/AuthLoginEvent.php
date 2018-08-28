<?php

namespace App\Events;

use App\User;
use App\UserSetting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthLoginEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(User $user)
    {
        if (empty($user->settings)) {
            $settings = new UserSetting();
            $settings->user_id = $user->id;
            $settings->save();
        }
    }
}
