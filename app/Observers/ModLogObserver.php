<?php

namespace App\Observers;

use App\Notification;
use App\ModLog;
use App\Services\ModLogService;
use Log;

class ModLogObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
}
