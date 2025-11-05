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
}
