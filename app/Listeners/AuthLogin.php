<?php

namespace App\Listeners;

use App\Models\UserDevice;
use App\Services\Account\AccountInitializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        if (! $user) {
            return;
        }

        app(AccountInitializer::class)->initialize($user);
        $this->userState($user);
        $this->userDevice($user);
        $this->userLanguage($user);
    }

    protected function userState($user)
    {
        if ($user->status != null) {
            $profile = $user->profile;
            if (! $profile) {
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
                    // code...
                    break;
            }
        }
    }

    protected function userDevice($user)
    {
        $device = DB::transaction(function () use ($user) {
            return UserDevice::firstOrCreate([
                'user_id' => $user->id,
                'ip' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent(), 180),
            ]);
        });
    }

    protected function userLanguage($user)
    {
        session()->put('locale', $user->language ?? config('app.locale'));
    }
}
