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

    /**
     * Handle the notification "created" event.
     *
     * @return void
     */
    public function created(Notification $notification)
    {
        NotificationService::set($notification->profile_id, $notification->id);
    }

    /**
     * Handle the notification "updated" event.
     *
     * @return void
     */
    public function updated(Notification $notification)
    {
        NotificationService::set($notification->profile_id, $notification->id);
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @return void
     */
    public function deleted(Notification $notification)
    {
        NotificationService::del($notification->profile_id, $notification->id);
    }

    /**
     * Handle the notification "restored" event.
     *
     * @return void
     */
    public function restored(Notification $notification)
    {
        NotificationService::set($notification->profile_id, $notification->id);
    }

    /**
     * Handle the notification "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Notification $notification)
    {
        NotificationService::del($notification->profile_id, $notification->id);
    }
}
