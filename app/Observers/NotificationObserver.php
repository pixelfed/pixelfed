<?php

namespace App\Observers;

use App\Notification;
use App\Services\NotificationService;

class NotificationObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
}
