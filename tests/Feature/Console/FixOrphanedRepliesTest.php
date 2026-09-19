<?php

use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| fix:orphaned-replies
|--------------------------------------------------------------------------
|
| Remote replies stored as top-level statuses can only be recognised by
| asking their origin. The command is read-only unless --fix is given.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();
});

function orphanSeedHosts(): void
{
    foreach (['remote.example', 'other.example', config('pixelfed.domain.app')] as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function orphanRemoteProfile(): Profile
{
    return Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'username' => '@bob@remote.example',
        'remote_url' => 'https://remote.example/users/bob',
        'last_fetched_at' => now(),
    ]);
}

/**
 * A remote status stored as top-level, plus what its origin says it is.
 */
function orphanStatus(Profile $author, string $path, ?string $inReplyTo, string $type = 'text'): Status
{
    $id = "https://remote.example/users/bob/statuses/{$path}";

    $status = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => $type,
        'uri' => "https://remote.example/@bob/{$path}",
        'url' => "https://remote.example/@bob/{$path}",
        'object_url' => $id,
        'local' => false,
    ]);

    Cache::put(Helpers::fetchCacheKey($id), [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $id,
        'type' => 'Note',
        'attributedTo' => $author->remote_url,
        'content' => '<p>hi</p>',
        'published' => now()->subHour()->toAtomString(),
        'inReplyTo' => $inReplyTo,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    ], 600);

    return $status;
}

it('reports orphans without changing anything by default', function () {
    $user = User::factory()->create();
    $user->refresh();
    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id]);
    $bob = orphanRemoteProfile();
    orphanSeedHosts();

    $orphan = orphanStatus($bob, '1', $parent->url());
    $missing = orphanStatus($bob, '2', 'https://other.example/users/alice/statuses/unknown');

    $this->artisan('fix:orphaned-replies')
        ->expectsOutputToContain('Orphaned replies found')
        ->assertSuccessful();

    expect($orphan->fresh()->in_reply_to_id)->toBeNull();
    expect($missing->fresh()->in_reply_to_id)->toBeNull();
    expect(Status::count())->toBe(3);
    Http::assertNothingSent();
    Queue::assertNotPushed(RemoteStatusDelete::class);
    Queue::assertNotPushed(FeedRemoveRemotePipeline::class);
});

it('relinks an orphan to its parent with --fix', function () {
    $user = User::factory()->create();
    $user->refresh();
    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id, 'reply_count' => 2]);
    $bob = orphanRemoteProfile();
    orphanSeedHosts();

    $orphan = orphanStatus($bob, '3', $parent->url());
    $topLevel = orphanStatus($bob, '4', null);

    $this->artisan('fix:orphaned-replies', ['--fix' => true])->assertSuccessful();

    $orphan->refresh();

    expect((int) $orphan->in_reply_to_id)->toBe((int) $parent->id);
    expect((int) $orphan->in_reply_to_profile_id)->toBe((int) $user->profile_id);
    expect($parent->fresh()->reply_count)->toBe(3);
    expect($topLevel->fresh()->in_reply_to_id)->toBeNull();
});

it('pulls a relinked media orphan out of home feeds', function () {
    $user = User::factory()->create();
    $user->refresh();
    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id]);
    $bob = orphanRemoteProfile();
    orphanSeedHosts();

    $orphan = orphanStatus($bob, '5', $parent->url(), 'photo');

    // Text only by default: a photo status is not a candidate.
    $this->artisan('fix:orphaned-replies', ['--fix' => true])->assertSuccessful();
    expect($orphan->fresh()->in_reply_to_id)->toBeNull();

    $this->artisan('fix:orphaned-replies', ['--fix' => true, '--media' => true])->assertSuccessful();

    expect((int) $orphan->fresh()->in_reply_to_id)->toBe((int) $parent->id);
    Queue::assertPushed(FeedRemoveRemotePipeline::class, 1);
});

it('leaves unrecoverable orphans alone unless --prune is given', function () {
    $bob = orphanRemoteProfile();
    orphanSeedHosts();

    $gone = 'https://other.example/users/alice/statuses/deleted';
    Cache::put(Helpers::fetchCacheKey($gone), false, 600);
    $orphan = orphanStatus($bob, '6', $gone);

    $this->artisan('fix:orphaned-replies', ['--fix' => true])->assertSuccessful();

    expect($orphan->fresh())->not->toBeNull();
    Queue::assertNotPushed(RemoteStatusDelete::class);

    $this->artisan('fix:orphaned-replies', ['--fix' => true, '--prune' => true, '--no-interaction' => true])
        ->assertSuccessful();

    Queue::assertPushedOn('delete', RemoteStatusDelete::class);
});

it('skips a status whose origin no longer serves it', function () {
    $bob = orphanRemoteProfile();
    orphanSeedHosts();

    $status = orphanStatus($bob, '7', 'https://other.example/users/alice/statuses/x');
    Cache::put(Helpers::fetchCacheKey($status->object_url), false, 600);

    $this->artisan('fix:orphaned-replies', ['--fix' => true, '--prune' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect($status->fresh())->not->toBeNull();
    Queue::assertNotPushed(RemoteStatusDelete::class);
});

it('refuses --prune without --fix', function () {
    $this->artisan('fix:orphaned-replies', ['--prune' => true])->assertFailed();
});
