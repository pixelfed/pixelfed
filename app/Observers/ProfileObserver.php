<?php

namespace App\Observers;

use App\Profile;
use App\Services\AccountService;

class ProfileObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Profile "created" event.
     *
     * @param  \App\Profile  $profile
     * @return void
     */
    public function created(Profile $profile)
    {
        //
    }

    /**
     * Handle the Profile "updated" event.
     *
     * @param  \App\Profile  $profile
     * @return void
     */
    public function updated(Profile $profile)
    {
        AccountService::del($profile->id);
    }

    /**
     * Handle the Profile "deleted" event.
     *
     * @param  \App\Profile  $profile
     * @return void
     */
    public function deleted(Profile $profile)
    {
        AccountService::del($profile->id);
    }

    /**
     * Handle the Profile "restored" event.
     *
     * @param  \App\Profile  $profile
     * @return void
     */
    public function restored(Profile $profile)
    {
        //
    }

    /**
     * Handle the Profile "force deleted" event.
     *
     * @param  \App\Profile  $profile
     * @return void
     */
    public function forceDeleted(Profile $profile)
    {
        //
    }
}
