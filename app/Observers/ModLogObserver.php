<?php

namespace App\Observers;

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

    /**
     * Handle the mod log "created" event.
     *
     * @return void
     */
    public function created(ModLog $modLog)
    {
        ModLogService::boot()->load($modLog)->fanout();
    }

    /**
     * Handle the mod log "updated" event.
     *
     * @return void
     */
    public function updated(ModLog $modLog)
    {
        ModLogService::boot()->load($modLog)->fanout();
    }

    /**
     * Handle the mod log "deleted" event.
     *
     * @return void
     */
    public function deleted(ModLog $modLog)
    {
        ModLogService::boot()->load($modLog)->unfanout();
    }

    /**
     * Handle the mod log "restored" event.
     *
     * @return void
     */
    public function restored(ModLog $modLog)
    {
        ModLogService::boot()->load($modLog)->fanout();
    }

    /**
     * Handle the mod log "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(ModLog $modLog)
    {
        ModLogService::boot()->load($modLog)->unfanout();
    }
}
