<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/pixelfed/v1/timelines/network null-account filtering
|--------------------------------------------------------------------------
|
| The non-cached network timeline path must drop statuses whose account failed
| to resolve (e.g. a deleted remote profile) so it never returns account=null,
| matching the cached path and the Mastodon API contract.
|
*/

beforeEach(function () {
    Redis::spy();
    config(['federation.network_timeline' => true]);
    config(['instance.timeline.network.cached' => false]);
});

function remotePublicStatus(Profile $author): Status
{
    return Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
    ]);
}

it('excludes statuses whose account failed to resolve', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $liveAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $deletedAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $liveStatus = remotePublicStatus($liveAuthor);
    $deletedStatus = remotePublicStatus($deletedAuthor);

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
