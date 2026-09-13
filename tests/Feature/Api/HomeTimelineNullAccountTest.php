<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\AccountService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/pixelfed/v1/timelines/home null-account filtering
|--------------------------------------------------------------------------
|
| The home timeline transforms each followed status via StatusService::get,
| which returns account=null once the author profile is deleted. The filter
| callback must drop those instead of dereferencing $s['account']['id'] (which
| in PHP 8 throws "Trying to access array offset on null" -> HTTP 500), matching
| every other timeline filter in the controller.
|
*/

beforeEach(function () {
    Redis::spy();
});

/**
 * Make $viewer follow $author.
 */
function follow(int $viewerPid, int $authorPid): void
{
    $f = new Follower;
    $f->profile_id = $viewerPid;
    $f->following_id = $authorPid;
    $f->save();
}

function homeStatus(int $authorPid): Status
{
    return Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);
}

it('returns 200 and drops a followed status whose author was deleted', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $liveAuthor = User::factory()->create();
    $liveAuthor->refresh();
    $deletedAuthor = User::factory()->create();
    $deletedAuthor->refresh();

    follow($viewer->profile_id, $liveAuthor->profile_id);
    follow($viewer->profile_id, $deletedAuthor->profile_id);

    $liveStatus = homeStatus($liveAuthor->profile_id);
    $deletedStatus = homeStatus($deletedAuthor->profile_id);

    // Warm the status cache while both authors resolve.
    StatusService::get($liveStatus->id, false);
    StatusService::get($deletedStatus->id, false);

    // Delete the second author's profile so AccountService::get() returns null,
    // making StatusService::get() return account=null for its status.
    $deletedAuthor->profile->status = 'delete';
    $deletedAuthor->profile->save();
    AccountService::del($deletedAuthor->profile_id);
    StatusService::del($deletedStatus->id);

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/home?limit=40')
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();

    // No entry has a null account.
    foreach ($res as $entry) {
        expect($entry['account'] ?? null)->not->toBeNull();
    }

    // The deleted-author status is filtered out; the live one remains.
    expect($ids)->not->toContain((string) $deletedStatus->id);
    expect($ids)->toContain((string) $liveStatus->id);
});

it('returns 200 for the min_id/max_id branch when an author was deleted', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $deletedAuthor = User::factory()->create();
    $deletedAuthor->refresh();

    follow($viewer->profile_id, $deletedAuthor->profile_id);

    $deletedStatus = homeStatus($deletedAuthor->profile_id);
    StatusService::get($deletedStatus->id, false);

    $deletedAuthor->profile->status = 'delete';
    $deletedAuthor->profile->save();
    AccountService::del($deletedAuthor->profile_id);
    StatusService::del($deletedStatus->id);

    // max_id exercises the ($min || $max) branch (the other filter callback).
    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/home?limit=40&max_id='.($deletedStatus->id + 1))
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();
    expect($ids)->not->toContain((string) $deletedStatus->id);
});
