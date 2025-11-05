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
}
