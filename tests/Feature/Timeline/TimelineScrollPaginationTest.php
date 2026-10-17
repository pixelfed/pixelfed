<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Timeline scroll pagination with created_at ranking
|--------------------------------------------------------------------------
|
| Feeds are ranked by created_at, not status id. A remote post can hold a low
| id but a recent date, so it sorts far from its id position. The next/prev
| Link cursors must follow display order; deriving them from the min/max id
| points the cursor at the wrong row and makes infinite scroll re-serve posts
| or stall. Each walk below follows the Link header like the web client and
| asserts every post appears exactly once.
|
*/

function parseNextMaxId(?string $link): ?string
{
    if (! $link) {
        return null;
    }
    if (preg_match('/max_id=(\d+)>; rel="next"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Create a status whose created_at is set explicitly so date order and id
 * order can be made to disagree.
 */
function statusAt(int $authorPid, Carbon $createdAt, bool $remote = false): Status
{
    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'local' => ! $remote,
        'uri' => $remote ? 'https://remote.example/p/'.bin2hex(random_bytes(6)) : null,
        'created_at' => $createdAt,
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

it('paginates the home feed without duplicates when id order differs from date order', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $local = User::factory()->create();
    $local->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $local->profile_id]);

    $remoteAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Follower::create(['profile_id' => $pid, 'following_id' => $remoteAuthor->id]);

    // Interleave local and remote posts so id order and date order diverge:
    // the remote posts are created last (highest ids) but back-dated so they
    // sort into the middle of the feed by created_at.
    $all = [];
    for ($i = 0; $i < 8; $i++) {
        $all[] = statusAt($local->profile_id, now()->subMinutes(10 * ($i + 1)));
    }
    // Remote posts get the newest ids but dates spread across the local range.
    for ($i = 0; $i < 4; $i++) {
        $all[] = statusAt($remoteAuthor->id, now()->subMinutes(15 + 20 * $i), true);
    }

    Redis::del(HomeTimelineService::CACHE_KEY.$pid);
    Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);
    HomeTimelineService::warmCache($pid, true, 400);

    $cached = collect(Redis::zrevrange(HomeTimelineService::CACHE_KEY.$pid, 0, -1))
        ->map(fn ($v) => (string) $v);

    expect($cached->count())->toBe(count($all));

    Passport::actingAs($viewer, ['read']);

    $limit = 3;
    $base = '/api/v1/timelines/home?limit='.$limit.'&_pe=1';
    $seen = [];
    $maxId = null;

    for ($page = 0; $page < 20; $page++) {
        $url = $maxId ? $base.'&max_id='.$maxId : $base;
        $resp = $this->getJson($url)->assertOk();
        $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count($ids) === 0) {
            break;
        }

        foreach ($ids as $id) {
            // A cursor that drifts off display order re-serves earlier posts.
            expect($seen)->not->toContain($id);
            $seen[] = $id;
        }

        $next = parseNextMaxId($resp->headers->get('Link'));
        if (! $next || $next === $maxId) {
            break;
        }
        $maxId = $next;
    }

    // Every cached post was served exactly once across the scroll.
    expect(collect($seen)->unique()->count())->toBe($cached->count());
    expect(collect($seen)->sort()->values()->all())
        ->toBe($cached->sort()->values()->all());

    Redis::del(HomeTimelineService::CACHE_KEY.$pid);
});

it('paginates the local feed without duplicates when id order differs from date order', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();

    $all = [];
    for ($i = 0; $i < 8; $i++) {
        $all[] = statusAt($author->profile_id, now()->subMinutes(10 * ($i + 1)));
    }

    Redis::del(PublicTimelineService::CACHE_KEY);
    PublicTimelineService::warmCache(true, 400);

    $cached = collect(Redis::zrevrange(PublicTimelineService::CACHE_KEY, 0, -1))
        ->map(fn ($v) => (string) $v);

    expect($cached->count())->toBeGreaterThanOrEqual(count($all));

    Passport::actingAs($viewer, ['read']);

    $limit = 3;
    $base = '/api/v1/timelines/public?limit='.$limit.'&_pe=1&local=1';
    $seen = [];
    $maxId = null;

    for ($page = 0; $page < 30; $page++) {
        $url = $maxId ? $base.'&max_id='.$maxId : $base;
        $resp = $this->getJson($url)->assertOk();
        $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count($ids) === 0) {
            break;
        }

        foreach ($ids as $id) {
            expect($seen)->not->toContain($id);
            $seen[] = $id;
        }

        $next = parseNextMaxId($resp->headers->get('Link'));
        if (! $next || $next === $maxId) {
            break;
        }
        $maxId = $next;
    }

    // Each seeded local post was served exactly once.
    $seededIds = collect($all)->pluck('id')->map(fn ($id) => (string) $id)->sort()->values();
    $seenSeeded = collect($seen)->filter(fn ($id) => $seededIds->contains($id))->sort()->values();
    expect($seenSeeded->all())->toBe($seededIds->all());

    Redis::del(PublicTimelineService::CACHE_KEY);
});
