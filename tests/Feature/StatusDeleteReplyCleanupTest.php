<?php

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Replies of a deleted status
|--------------------------------------------------------------------------
|
| Both delete pipelines used to null in_reply_to_id on every reply, which
| turned comments into top-level posts. Remote replies are now deleted with
| the parent, local replies are detached.
|
*/

beforeEach(function () {
    Redis::spy();
    Http::fake();

    config(['federation.activitypub.enabled' => false]);
});

function cleanupRemoteProfile(): Profile
{
    return Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'remote_url' => 'https://remote.example/users/bob',
    ]);
}

function cleanupRemoteReply(Profile $author, Status $parent, string $path): Status
{
    $id = "https://remote.example/users/bob/statuses/{$path}";

    return Status::factory()->create([
        'profile_id' => $author->id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $parent->profile_id,
        'uri' => $id,
        'url' => $id,
        'object_url' => $id,
        'local' => false,
    ]);
}

it('queues remote replies for deletion when a local status is deleted', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->refresh();
    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id]);
    $reply = cleanupRemoteReply(cleanupRemoteProfile(), $parent, '1');

    (new StatusDelete($parent))->handle();

    Queue::assertPushedOn('delete', RemoteStatusDelete::class);

    // Still attached while it waits: hidden, never a top-level post.
    expect((int) $reply->fresh()->in_reply_to_id)->toBe((int) $parent->id);
});

it('removes remote replies, and their remote replies, when the jobs run', function () {
    $user = User::factory()->create();
    $user->refresh();
    $bob = cleanupRemoteProfile();

    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id]);
    $reply = cleanupRemoteReply($bob, $parent, '2');
    $nested = cleanupRemoteReply($bob, $reply, '3');

    (new StatusDelete($parent))->handle();

    expect(Status::withTrashed()->find($reply->id))->toBeNull();
    expect(Status::withTrashed()->find($nested->id))->toBeNull();
});

it('removes remote replies when a remote status is deleted', function () {
    $bob = cleanupRemoteProfile();

    $parent = Status::factory()->photo()->create([
        'profile_id' => $bob->id,
        'uri' => 'https://remote.example/users/bob/statuses/root',
        'url' => 'https://remote.example/users/bob/statuses/root',
        'object_url' => 'https://remote.example/users/bob/statuses/root',
        'local' => false,
    ]);
    $reply = cleanupRemoteReply($bob, $parent, '4');

    (new RemoteStatusDelete($parent))->handle();

    expect(Status::withTrashed()->find($parent->id))->toBeNull();
    expect(Status::withTrashed()->find($reply->id))->toBeNull();
});

it('keeps and detaches local replies', function () {
    $user = User::factory()->create();
    $user->refresh();
    $commenter = User::factory()->create();
    $commenter->refresh();

    $parent = Status::factory()->photo()->create(['profile_id' => $user->profile_id]);
    $comment = Status::factory()->create([
        'profile_id' => $commenter->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $user->profile_id,
    ]);

    (new StatusDelete($parent))->handle();

    expect($comment->fresh())->not->toBeNull();
    expect($comment->fresh()->in_reply_to_id)->toBeNull();
});
