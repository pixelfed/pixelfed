<?php

use App\Models\Status;
use App\Models\User;
use App\Services\DiscoverService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DiscoverService daily id pool
|--------------------------------------------------------------------------
|
| getDailyIdPool() de-duplicates to one photo per author. The old query did
| `select id ... group by profile_id`, which throws ERROR 1055 under
| ONLY_FULL_GROUP_BY (every strict MySQL/MariaDB deployment) and, on drivers
| where it did run, was gated off entirely so prolific authors flooded the
| pool. It now uses MAX(id)+GROUP BY: valid on every driver, one newest post
| per author.
|
*/

beforeEach(function () {
    Redis::spy();
});

function publicPhoto(int $profileId): Status
{
    return Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'is_nsfw' => false,
        'uri' => null,
        'local' => true,
    ]);
}

it('returns exactly one id per author', function () {
    $author = User::factory()->create();
    $author->refresh();

    // Several public photos by the same author.
    publicPhoto($author->profile_id);
    publicPhoto($author->profile_id);
    publicPhoto($author->profile_id);

    $pool = DiscoverService::getDailyIdPool();

    expect($pool)->toHaveCount(1);
});

it('keeps the newest post per author', function () {
    $author = User::factory()->create();
    $author->refresh();

    $ids = collect([
        publicPhoto($author->profile_id)->id,
        publicPhoto($author->profile_id)->id,
        publicPhoto($author->profile_id)->id,
    ]);
    $newestId = $ids->max();

    $pool = DiscoverService::getDailyIdPool();

    expect($pool->map(fn ($id) => (int) $id)->all())->toBe([(int) $newestId]);
});

it('includes one id from each of several authors', function () {
    $a = User::factory()->create();
    $a->refresh();
    $b = User::factory()->create();
    $b->refresh();

    publicPhoto($a->profile_id);
    publicPhoto($a->profile_id);
    publicPhoto($b->profile_id);

    $pool = DiscoverService::getDailyIdPool()->map(fn ($id) => (int) $id);

    expect($pool)->toHaveCount(2);
});
