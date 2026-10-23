<?php

use App\Jobs\GroupsPipeline\NewCommentPipeline;
use App\Models\Group;
use App\Models\GroupComment;
use App\Models\GroupPost;
use App\Models\User;
use App\Services\GroupPostService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Http::fake();
});

function makeGroupFixture(): array
{
    $user = User::factory()->create();
    $user->refresh();

    $group = new Group;
    $group->profile_id = $user->profile_id;
    $group->name = 'Test Group';
    $group->local = true;
    $group->is_private = false;
    $group->save();

    $post = new GroupPost;
    $post->group_id = $group->id;
    $post->profile_id = $user->profile_id;
    $post->type = 'text';
    $post->caption = 'Parent post';
    $post->visibility = 'public';
    $post->reply_count = 0;
    $post->save();

    return [$user, $group, $post];
}

function makeTopLevelComment(Group $group, GroupPost $post, int $profileId): GroupComment
{
    $comment = new GroupComment;
    $comment->group_id = $group->id;
    $comment->profile_id = $profileId;
    $comment->status_id = $post->id;
    $comment->caption = 'A comment';
    $comment->visibility = 'public';
    $comment->is_nsfw = false;
    $comment->local = true;
    $comment->save();

    return $comment;
}

it('invalidates and rebuilds the cached post payload when a top-level comment is added', function () {
    [$user, $group, $post] = makeGroupFixture();

    $warm = GroupPostService::get($group->id, $post->id);
    expect((int) $warm['reply_count'])->toBe(0);

    $comment = makeTopLevelComment($group, $post, $user->profile_id);

    (new NewCommentPipeline($post, $comment))->handle();

    expect((int) GroupPostService::get($group->id, $post->id)['reply_count'])->toBe(1);
});

it('invalidates the thread-root post cache when a reply to a comment is added', function () {
    [$user, $group, $post] = makeGroupFixture();

    $comment = makeTopLevelComment($group, $post, $user->profile_id);
    (new NewCommentPipeline($post, $comment))->handle();

    GroupPostService::get($group->id, $post->id);

    $reply = new GroupComment;
    $reply->group_id = $group->id;
    $reply->profile_id = $user->profile_id;
    $reply->status_id = $post->id;
    $reply->in_reply_to_id = $comment->id;
    $reply->caption = 'A reply';
    $reply->visibility = 'public';
    $reply->is_nsfw = false;
    $reply->local = true;
    $reply->save();

    (new NewCommentPipeline($comment, $reply))->handle();

    expect((int) GroupPostService::get($group->id, $post->id)['reply_count'])->toBe(2);
});

it('invalidates the parent post cache when a photo comment is added', function () {
    [$user, $group, $post] = makeGroupFixture();

    GroupPostService::get($group->id, $post->id);

    $photoComment = new GroupComment;
    $photoComment->group_id = $group->id;
    $photoComment->profile_id = $user->profile_id;
    $photoComment->status_id = $post->id;
    $photoComment->caption = 'Photo comment';
    $photoComment->visibility = 'draft';
    $photoComment->is_nsfw = false;
    $photoComment->local = true;
    $photoComment->save();

    (new NewCommentPipeline($post, $photoComment))->handle();

    expect((int) GroupPostService::get($group->id, $post->id)['reply_count'])->toBe(1);
});
