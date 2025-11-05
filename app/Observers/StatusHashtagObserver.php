<?php

namespace App\Observers;

use DB;
use App\StatusHashtag;
use App\Services\StatusHashtagService;
use App\Jobs\HomeFeedPipeline\HashtagInsertFanoutPipeline;
use App\Jobs\HomeFeedPipeline\HashtagRemoveFanoutPipeline;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StatusHashtagObserver implements ShouldHandleEventsAfterCommit
{

}
