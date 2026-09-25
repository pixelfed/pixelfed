<?php

use App\Services\StoryIndexService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StoryIndexService::clearStoryCache
|--------------------------------------------------------------------------
|
| clearStoryCache globbed story:* and deleted everything except following:
| keys, wiping the story:rebuilding rebuild lock. It also double-prefixed the
| del() targets so it was a no-op under a non-empty REDIS_PREFIX. It must
| delete real story keys while preserving the rebuild lock.
|
*/

function callClearStoryCache(): void
{
    $svc = new StoryIndexService;
    $ref = new ReflectionMethod($svc, 'clearStoryCache');
    $ref->setAccessible(true);
    $ref->invoke($svc);
}

beforeEach(function () {
    foreach (['story:rebuilding', 'story:foo', 'story:active_authors', 'story:following:1'] as $k) {
        Redis::del($k);
    }
});

afterEach(function () {
    foreach (['story:rebuilding', 'story:foo', 'story:active_authors', 'story:following:1'] as $k) {
        Redis::del($k);
    }
});

it('deletes story keys but preserves the rebuild lock and follower carousels', function () {
    Redis::set('story:rebuilding', '1');
    Redis::set('story:foo', 'x');
    Redis::set('story:active_authors', 'y');
    Redis::set('story:following:1', 'z');

    callClearStoryCache();

    // The rebuild mutex must survive so a concurrent rebuild is blocked.
    expect((bool) Redis::exists('story:rebuilding'))->toBeTrue();
    // Follower carousels are preserved.
    expect((bool) Redis::exists('story:following:1'))->toBeTrue();

    // Ordinary story keys are actually deleted (prefix stripped correctly).
    expect((bool) Redis::exists('story:foo'))->toBeFalse();
    expect((bool) Redis::exists('story:active_authors'))->toBeFalse();
});
