<?php

namespace App\Observers;

use App\Like;
use App\Services\LikeService;

class LikeObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Like "created" event.
     *
     * @param  \App\Models\Like  $like
     * @return void
     */
    public function created(Like $like)
    {
        LikeService::add($like->profile_id, $like->status_id);
    }

    /**
     * Handle the Like "updated" event.
     *
     * @param  \App\Models\Like  $like
     * @return void
     */
    public function updated(Like $like)
    {
        //
    }

    /**
     * Handle the Like "deleted" event.
     *
     * @param  \App\Models\Like  $like
     * @return void
     */
    public function deleted(Like $like)
    {
        LikeService::remove($like->profile_id, $like->status_id);
    }

    /**
     * Handle the Like "restored" event.
     *
     * @param  \App\Models\Like  $like
     * @return void
     */
    public function restored(Like $like)
    {
        LikeService::add($like->profile_id, $like->status_id);
    }

    /**
     * Handle the Like "force deleted" event.
     *
     * @param  \App\Models\Like  $like
     * @return void
     */
    public function forceDeleted(Like $like)
    {
        LikeService::remove($like->profile_id, $like->status_id);
    }
}
