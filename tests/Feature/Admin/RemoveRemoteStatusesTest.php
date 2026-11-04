<?php

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\Status\RemoteStatusPurgeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();
});

function purgeOldId(int $n): int
{
    return RemoteStatusPurgeService::maxIdForCutoff(now()->subDays(200)) + $n;
}

function purgeRemoteProfile(): Profile
{
    return Profile::factory()->remote()->create();
}

function purgeLocalProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function purgeStatus(Profile $profile, int $n, array $attributes = []): Status
{
    return Status::factory()->create(array_merge([
        'id' => purgeOldId($n),
        'profile_id' => $profile->id,
        'local' => $profile->domain === null,
        'uri' => $profile->domain ? 'https://'.$profile->domain.'/p/'.$n : null,
        'object_url' => $profile->domain ? 'https://'.$profile->domain.'/objects/'.$n : null,
        'created_at' => now()->subDays(200),
    ], $attributes));
}

function purgeExists(Status|int $status): bool
{
    $id = $status instanceof Status ? $status->id : $status;

    return DB::table('statuses')->where('id', $id)->exists();
}

function runPurge(array $options = []): void
{
    test()->artisan('statuses:remove-remote', array_merge(['--force' => true], $options))
        ->assertSuccessful();
}

it('maps the cutoff to the first snowflake id minted at that moment', function () {
    $cutoff = now()->subDays(30);
    $id = RemoteStatusPurgeService::maxIdForCutoff($cutoff);

    $ms = ($id >> 22) + RemoteStatusPurgeService::SNOWFLAKE_EPOCH_MS;

    expect($ms)->toBe((int) $cutoff->getTimestampMs())
        ->and(RemoteStatusPurgeService::maxIdForCutoff(now()->setDate(2018, 1, 1)))->toBe(0);
});

it('hard deletes an old untouched remote status and its side rows', function () {
    $remote = purgeRemoteProfile();
    $otherRemote = purgeRemoteProfile();
    $status = purgeStatus($remote, 1);

    DB::table('likes')->insert(['profile_id' => $otherRemote->id, 'status_id' => $status->id, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('mentions')->insert(['profile_id' => $otherRemote->id, 'status_id' => $status->id, 'created_at' => now(), 'updated_at' => now()]);
    $hashtagId = DB::table('hashtags')->insertGetId(['name' => 'cats', 'slug' => 'cats', 'cached_count' => 5, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('status_hashtags')->insert(['status_id' => $status->id, 'hashtag_id' => $hashtagId, 'profile_id' => $remote->id, 'status_visibility' => 'public', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('media')->insert(['status_id' => $status->id, 'profile_id' => $remote->id, 'media_path' => 'https://'.$remote->domain.'/a.jpg', 'remote_url' => 'https://'.$remote->domain.'/a.jpg', 'remote_media' => true, 'created_at' => now(), 'updated_at' => now()]);

    runPurge();

    expect(purgeExists($status))->toBeFalse()
        ->and(DB::table('likes')->where('status_id', $status->id)->exists())->toBeFalse()
        ->and(DB::table('mentions')->where('status_id', $status->id)->exists())->toBeFalse()
        ->and(DB::table('status_hashtags')->where('status_id', $status->id)->exists())->toBeFalse()
        ->and(DB::table('media')->where('status_id', $status->id)->exists())->toBeFalse()
        ->and((int) DB::table('hashtags')->where('id', $hashtagId)->value('cached_count'))->toBe(4);

    Queue::assertNotPushed(MediaDeletePipeline::class);
});

it('leaves local statuses and recently fetched remote statuses alone', function () {
    $local = purgeStatus(purgeLocalProfile(), 1);
    $recent = Status::factory()->create([
        'profile_id' => purgeRemoteProfile()->id,
        'local' => false,
        'uri' => 'https://remote.test/p/recent',
    ]);

    runPurge();

    expect(purgeExists($local))->toBeTrue()
        ->and(purgeExists($recent))->toBeTrue();
});

it('identifies remote shares by author even though they have no uri or object_url', function () {
    $remote = purgeRemoteProfile();
    $original = purgeStatus(purgeRemoteProfile(), 1);
    $share = purgeStatus($remote, 2, [
        'type' => 'share',
        'reblog_of_id' => $original->id,
        'uri' => null,
        'object_url' => null,
        'local' => true,
    ]);

    runPurge();

    expect(purgeExists($share))->toBeFalse()
        ->and(purgeExists($original))->toBeFalse();
});

it('keeps remote statuses a local account interacted with', function () {
    $local = purgeLocalProfile();
    $remote = purgeRemoteProfile();

    $liked = purgeStatus($remote, 1);
    DB::table('likes')->insert(['profile_id' => $local->id, 'status_id' => $liked->id, 'created_at' => now(), 'updated_at' => now()]);

    $bookmarked = purgeStatus($remote, 2);
    DB::table('bookmarks')->insert(['profile_id' => $local->id, 'status_id' => $bookmarked->id, 'created_at' => now(), 'updated_at' => now()]);

    $mentioning = purgeStatus($remote, 3);
    DB::table('mentions')->insert(['profile_id' => $local->id, 'status_id' => $mentioning->id, 'created_at' => now(), 'updated_at' => now()]);

    $shared = purgeStatus($remote, 4);
    purgeStatus($local, 5, ['type' => 'share', 'reblog_of_id' => $shared->id]);

    $repliedTo = purgeStatus($remote, 6);
    purgeStatus($local, 7, ['type' => 'reply', 'in_reply_to_id' => $repliedTo->id]);

    $collected = purgeStatus($remote, 8);
    DB::table('collection_items')->insert(['collection_id' => 1, 'object_type' => 'post', 'object_id' => $collected->id, 'created_at' => now(), 'updated_at' => now()]);

    $untouched = purgeStatus($remote, 9);

    runPurge();

    foreach ([$liked, $bookmarked, $mentioning, $shared, $repliedTo, $collected] as $status) {
        expect(purgeExists($status))->toBeTrue();
    }
    expect(purgeExists($untouched))->toBeFalse();
});

it('keeps remote replies to and shares of local posts', function () {
    $localPost = purgeStatus(purgeLocalProfile(), 1);
    $remote = purgeRemoteProfile();

    $reply = purgeStatus($remote, 2, ['type' => 'reply', 'in_reply_to_id' => $localPost->id]);
    $share = purgeStatus($remote, 3, ['type' => 'share', 'reblog_of_id' => $localPost->id, 'uri' => null, 'object_url' => null]);

    runPurge();

    expect(purgeExists($reply))->toBeTrue()
        ->and(purgeExists($share))->toBeTrue();
});

it('clears a whole stale remote thread in one pass', function () {
    $remote = purgeRemoteProfile();
    $root = purgeStatus($remote, 1);
    $child = purgeStatus(purgeRemoteProfile(), 2, ['type' => 'reply', 'in_reply_to_id' => $root->id]);
    $grandchild = purgeStatus(purgeRemoteProfile(), 3, ['type' => 'reply', 'in_reply_to_id' => $child->id]);

    runPurge(['--batch' => 100]);

    expect(purgeExists($root))->toBeFalse()
        ->and(purgeExists($child))->toBeFalse()
        ->and(purgeExists($grandchild))->toBeFalse();
});

it('keeps an old status while a recent reply or boost still points at it', function () {
    $remote = purgeRemoteProfile();
    $replied = purgeStatus($remote, 1);
    $boosted = purgeStatus($remote, 2);

    Status::factory()->create([
        'profile_id' => purgeRemoteProfile()->id,
        'local' => false,
        'type' => 'reply',
        'in_reply_to_id' => $replied->id,
        'uri' => 'https://remote.test/p/new-reply',
    ]);
    Status::factory()->create([
        'profile_id' => purgeRemoteProfile()->id,
        'local' => false,
        'type' => 'share',
        'reblog_of_id' => $boosted->id,
    ]);

    runPurge();

    expect(purgeExists($replied))->toBeTrue()
        ->and(purgeExists($boosted))->toBeTrue();
});

it('decrements counters on posts that survive', function () {
    $local = purgeLocalProfile();
    $remote = purgeRemoteProfile();

    $parent = purgeStatus($remote, 1, ['reply_count' => 2, 'reblogs_count' => 1]);
    DB::table('likes')->insert(['profile_id' => $local->id, 'status_id' => $parent->id, 'created_at' => now(), 'updated_at' => now()]);

    purgeStatus(purgeRemoteProfile(), 2, ['type' => 'reply', 'in_reply_to_id' => $parent->id]);
    purgeStatus(purgeRemoteProfile(), 3, ['type' => 'share', 'reblog_of_id' => $parent->id, 'uri' => null, 'object_url' => null]);

    runPurge();

    $fresh = DB::table('statuses')->where('id', $parent->id)->first();

    expect($fresh)->not->toBeNull()
        ->and((int) $fresh->reply_count)->toBe(1)
        ->and((int) $fresh->reblogs_count)->toBe(0);
});

it('treats soft deleted remote statuses as garbage, unless moderation references them', function () {
    $local = purgeLocalProfile();
    $remote = purgeRemoteProfile();

    $trashed = purgeStatus($remote, 1, ['deleted_at' => now()->subDays(100)]);
    DB::table('likes')->insert(['profile_id' => $local->id, 'status_id' => $trashed->id, 'created_at' => now(), 'updated_at' => now()]);

    $reported = purgeStatus($remote, 2, ['deleted_at' => now()->subDays(100)]);
    DB::table('reports')->insert([
        'profile_id' => $local->id,
        'object_id' => $reported->id,
        'object_type' => Status::class,
        'reported_profile_id' => $remote->id,
        'type' => 'spam',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runPurge();

    expect(purgeExists($trashed))->toBeFalse()
        ->and(purgeExists($reported))->toBeTrue();
});

it('sweeps soft deleted shares left pointing at a purged post', function () {
    $original = purgeStatus(purgeRemoteProfile(), 1);
    $undone = Status::factory()->create([
        'profile_id' => purgeRemoteProfile()->id,
        'local' => false,
        'type' => 'share',
        'reblog_of_id' => $original->id,
        'deleted_at' => now(),
    ]);

    runPurge();

    expect(purgeExists($original))->toBeFalse()
        ->and(purgeExists($undone))->toBeFalse();
});

it('queues locally cached remote media for file deletion', function () {
    $remote = purgeRemoteProfile();
    $status = purgeStatus($remote, 1);
    $cachedId = DB::table('media')->insertGetId([
        'status_id' => $status->id,
        'profile_id' => $remote->id,
        'media_path' => 'public/m/_v2/1/abc/def/cached.jpg',
        'remote_url' => 'https://'.$remote->domain.'/cached.jpg',
        'remote_media' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runPurge();

    expect(purgeExists($status))->toBeFalse()
        ->and(DB::table('media')->where('id', $cachedId)->value('status_id'))->toBeNull();

    Queue::assertPushed(MediaDeletePipeline::class, 1);
});

it('keeps statuses with cached media when --skip-media is set', function () {
    $remote = purgeRemoteProfile();
    $status = purgeStatus($remote, 1);
    DB::table('media')->insert([
        'status_id' => $status->id,
        'profile_id' => $remote->id,
        'media_path' => 'public/m/_v2/1/abc/def/cached.jpg',
        'remote_media' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runPurge(['--skip-media' => true]);

    expect(purgeExists($status))->toBeTrue();
    Queue::assertNotPushed(MediaDeletePipeline::class);
});

it('changes nothing on a dry run', function () {
    $status = purgeStatus(purgeRemoteProfile(), 1);

    runPurge(['--dry-run' => true]);

    expect(purgeExists($status))->toBeTrue();
});

it('resumes below --start-id, leaving rows at or above it alone', function () {
    $remote = purgeRemoteProfile();
    $below = purgeStatus($remote, 10);
    $resumePoint = purgeStatus($remote, 20);
    $above = purgeStatus($remote, 30);

    runPurge(['--start-id' => $resumePoint->id]);

    expect(purgeExists($below))->toBeFalse()
        ->and(purgeExists($resumePoint))->toBeTrue()
        ->and(purgeExists($above))->toBeTrue();
});
