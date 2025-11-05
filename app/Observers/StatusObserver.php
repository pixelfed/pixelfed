<?php

namespace App\Observers;

use App\Status;
use App\Services\ProfileStatusService;
use Cache;
use App\Models\ImportPost;
use App\Services\ImportService;
use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;

class StatusObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;
}
