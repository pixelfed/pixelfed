<?php

namespace App\Listeners;

use App\AccountLog;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogFailedLogin
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
        $request = request();

        if(!$user) {
            return;
        }
        
        $log = new AccountLog();
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = 'App\User';
        $log->action = 'auth.failed';
        $log->message = 'Failed login attempt';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();
    }
}
