<?php

use App\Models\Profile;
use App\Models\Status;
use App\Services\NetworkTimelineService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

function remoteNetworkStatus(string $scope = 'public'): Status
{
    $author = Profile::factory()->create([
        'user_id' => null,
        'domain' => 'remote.example',
    ]);

    return Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => $scope,
        'visibility' => $scope,
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subMinutes(5),
    ]);
}

it('adds an eligible remote post to the network timeline on ingest', function () {
    $status = remoteNetworkStatus();

    Helpers::handleStatusPostProcessing($status, $status->profile_id, $status->uri);

    $cached = collect(NetworkTimelineService::get(0, -1))->map(fn ($id) => (int) $id)->all();

    expect($cached)->toContain((int) $status->id);
});
