<?php

namespace App\Observers;

use App\HashtagFollow;
use App\Services\HashtagFollowService;
use App\Jobs\HomeFeedPipeline\HashtagUnfollowPipeline;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class HashtagFollowObserver implements ShouldHandleEventsAfterCommit
{

}
