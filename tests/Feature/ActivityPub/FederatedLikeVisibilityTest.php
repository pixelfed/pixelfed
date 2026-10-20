<?php

use App\Jobs\LikePipeline\LikePipeline;
use App\Models\Follower;
use App\Models\Like;
use App\Models\Mention;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();

    config([
        'federation.activitypub.enabled' => true,
    ]);
});

function flvSeedHosts(array $hosts = ['remote.example']): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => Helpers::URL_OK, 'ips' => ['203.0.113.40']],
            3600
        );
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function flvLocalStatus(array $overrides = []): Status
{
    $user = User::factory()->create();
    $user->refresh();

    return Status::factory()->create(array_merge([
        'profile_id' => $user->profile->id,
    ], $overrides));
}

function flvRemoteLiker(string $username = 'liker'): Profile
{
    $actor = "https://remote.example/users/{$username}";

    return Profile::factory()->remote()->create([
        'username' => "{$username}@remote.example",
        'domain' => 'remote.example',
        'remote_url' => $actor,
    ]);
}

function flvLike(Profile $liker, Status $status): void
{
    $payload = [
        'type' => 'Like',
        'actor' => $liker->remote_url,
        'object' => $status->url(),
    ];

    (new Inbox([], null, $payload))->handleLikeActivity();
}

it('records a Like on a public local status from a remote actor', function () {
    flvSeedHosts();
    $status = flvLocalStatus();
    $liker = flvRemoteLiker();

    flvLike($liker, $status);

    expect(Like::where('profile_id', $liker->id)->where('status_id', $status->id)->exists())->toBeTrue()
        ->and($status->fresh()->likes_count)->toBe(1);

    Queue::assertPushed(LikePipeline::class);
});

it('rejects a Like on a private local status from a non-follower', function () {
    flvSeedHosts();
    $status = flvLocalStatus(['scope' => 'private', 'visibility' => 'private']);
    $liker = flvRemoteLiker();

    flvLike($liker, $status);

    expect(Like::where('profile_id', $liker->id)->where('status_id', $status->id)->exists())->toBeFalse()
        ->and($status->fresh()->likes_count)->toBe(0);

    Queue::assertNotPushed(LikePipeline::class);
});

it('records a Like on a private local status from an accepted follower', function () {
    flvSeedHosts();
    $status = flvLocalStatus(['scope' => 'private', 'visibility' => 'private']);
    $liker = flvRemoteLiker();

    Follower::create([
        'profile_id' => $liker->id,
        'following_id' => $status->profile_id,
        'local_profile' => false,
    ]);
    FollowerService::add($liker->id, $status->profile_id);

    flvLike($liker, $status);

    expect(Like::where('profile_id', $liker->id)->where('status_id', $status->id)->exists())->toBeTrue()
        ->and($status->fresh()->likes_count)->toBe(1);

    Queue::assertPushed(LikePipeline::class);
});

it('rejects a Like on a direct local status from a non-recipient', function () {
    flvSeedHosts();
    $status = flvLocalStatus(['scope' => 'direct', 'visibility' => 'direct']);
    $liker = flvRemoteLiker();

    flvLike($liker, $status);

    expect(Like::where('profile_id', $liker->id)->where('status_id', $status->id)->exists())->toBeFalse()
        ->and($status->fresh()->likes_count)->toBe(0);

    Queue::assertNotPushed(LikePipeline::class);
});

it('rejects a Like on a direct local status even when the actor is mentioned', function () {
    flvSeedHosts();
    $status = flvLocalStatus(['scope' => 'direct', 'visibility' => 'direct']);
    $liker = flvRemoteLiker();

    Mention::create([
        'status_id' => $status->id,
        'profile_id' => $liker->id,
        'local' => false,
    ]);

    flvLike($liker, $status);

    expect(Like::where('profile_id', $liker->id)->where('status_id', $status->id)->exists())->toBeFalse()
        ->and($status->fresh()->likes_count)->toBe(0);

    Queue::assertNotPushed(LikePipeline::class);
});
