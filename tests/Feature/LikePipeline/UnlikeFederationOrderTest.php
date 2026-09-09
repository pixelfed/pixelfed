<?php

use App\Jobs\LikePipeline\UnlikePipeline;
use App\Models\Like;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // LikeObserver + StatusService::refresh touch Redis; fake it so these
    // tests run without a live Redis server.
    Redis::spy();
});

/*
|--------------------------------------------------------------------------
| UnlikePipeline federation ordering
|--------------------------------------------------------------------------
|
| The Undo/Unlike must be federated BEFORE the Like is deleted locally.
| Otherwise a federation failure deletes the Like, and the retry is silently
| dropped (deleteWhenMissingModels), so the unlike never federates.
|
*/

it('keeps the Like when federation fails so a retry can still deliver', function () {
    $liker = User::factory()->create();
    $liker->refresh();

    // Remote status (owned by a different, remote profile) with a url so the
    // federation branch is taken.
    $remoteAuthor = Profile::factory()->create([
        'user_id' => null,
        'domain' => 'remote.example',
    ]);
    $status = Status::factory()->create([
        'profile_id' => $remoteAuthor->id,
        'type' => 'photo',
        'local' => false,
        'url' => 'https://remote.example/p/1',
        'likes_count' => 1,
    ]);

    $like = new Like;
    $like->profile_id = $liker->profile_id;
    $like->status_id = $status->id;
    $like->save();

    // Federation throws (simulating a timeout). Because delivery runs before
    // deletion, the Like must survive.
    $job = Mockery::mock(UnlikePipeline::class, [$like])->makePartial();
    $job->shouldReceive('remoteLikeDeliver')->andThrow(new RuntimeException('federation timeout'));

    try {
        $job->handle();
    } catch (RuntimeException $e) {
        // expected
    }

    expect(Like::find($like->id))->not->toBeNull();
});

it('deletes the Like for a local unlike with no federation', function () {
    $liker = User::factory()->create();
    $liker->refresh();
    $author = User::factory()->create();
    $author->refresh();

    // Local status (no url) -> federation branch skipped.
    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'local' => true,
        'url' => null,
        'likes_count' => 1,
    ]);

    $like = new Like;
    $like->profile_id = $liker->profile_id;
    $like->status_id = $status->id;
    $like->save();

    (new UnlikePipeline($like))->handle();

    expect(Like::find($like->id))->toBeNull();
    expect($status->fresh()->likes_count)->toBe(0);
});
