<?php

use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveDomainPipeline;
use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Jobs\HomeFeedPipeline\HashtagInsertFanoutPipeline;
use App\Jobs\HomeFeedPipeline\HashtagRemoveFanoutPipeline;
use App\Models\StatusHashtag;
use Illuminate\Queue\Middleware\WithoutOverlapping;

function overlapMiddleware(object $job): WithoutOverlapping
{
    $found = collect($job->middleware())->first(fn ($m) => $m instanceof WithoutOverlapping);

    expect($found)->not->toBeNull();

    return $found;
}

it('sets an overlap lock expiry equal to the job timeout', function (object $job) {
    $middleware = overlapMiddleware($job);

    expect($middleware->expiresAfter)->toBe($job->timeout)
        ->and($middleware->expiresAfter)->toBeGreaterThan(0);
})->with([
    'FeedFollowPipeline' => fn () => new FeedFollowPipeline(1, 2),
    'FeedInsertPipeline' => fn () => new FeedInsertPipeline(1, 2),
    'FeedInsertRemotePipeline' => fn () => new FeedInsertRemotePipeline(1, 2),
    'FeedRemoveDomainPipeline' => fn () => new FeedRemoveDomainPipeline(1, 'example.com'),
    'FeedRemovePipeline' => fn () => new FeedRemovePipeline(1, 2),
    'FeedRemoveRemotePipeline' => fn () => new FeedRemoveRemotePipeline(1, 2),
    'FeedUnfollowPipeline' => fn () => new FeedUnfollowPipeline(1, 2),
    'FeedWarmCachePipeline' => fn () => new FeedWarmCachePipeline(1),
    'HashtagInsertFanoutPipeline' => function () {
        $tag = new StatusHashtag;
        $tag->id = 1;

        return new HashtagInsertFanoutPipeline($tag);
    },
    'HashtagRemoveFanoutPipeline' => fn () => new HashtagRemoveFanoutPipeline(1, 2),
]);
