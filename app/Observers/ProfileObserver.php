<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Observers;

use App\Profile;
use App\Services\AccountService;

class ProfileObserver
{
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
