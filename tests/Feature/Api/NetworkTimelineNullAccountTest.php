<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\AccountService;
use App\Services\NetworkTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/pixelfed/v1/timelines/network null-account filtering
|--------------------------------------------------------------------------
|
| The network timeline must drop statuses whose account failed to resolve
| (e.g. a deleted remote profile) so it never returns account=null, matching
| the Mastodon API contract.
|
*/

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

function remotePublicStatus(Profile $author): Status
{
    return Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://remote.example/p/'.bin2hex(random_bytes(6)),
    ]);
}

it('excludes statuses whose account failed to resolve', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $liveAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $deletedAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $liveStatus = remotePublicStatus($liveAuthor);
    $deletedStatus = remotePublicStatus($deletedAuthor);

    // Both posts are present in the cached feed.
    NetworkTimelineService::add($liveStatus->id);
    NetworkTimelineService::add($deletedStatus->id);

    // Mark the second author deleted so AccountService::get() returns null.
    $deletedAuthor->status = 'delete';
    $deletedAuthor->save();
    AccountService::del($deletedAuthor->id);

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/network?limit=30')
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
