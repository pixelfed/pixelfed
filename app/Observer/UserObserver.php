<?php

namespace App\Observers;

use App\{Profile, User};

class UserObserver
{
    /**
     * Listen to the User created event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function saved(User $user)
    {
        if($user->has('profile')->count() == 0) {
            $profile = new Profile;
            $profile->user_id = $user->id;
            $profile->username = $user->username;
            $profile->save();
        }
    }

}