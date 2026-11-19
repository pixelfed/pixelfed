<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\FollowerService;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Reply visibility on GET /api/v2/statuses/{id}/replies
|--------------------------------------------------------------------------
|
| The parent post and every reply must be visible to the viewer: private
| parents only to followers, followers-only replies only to the reply
| author's followers or the account replied to, never direct replies, and
| never replies from accounts the viewer blocks.
|
*/

function statusRepliesUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function statusRepliesFollow(User $actor, User $target): void
{
    Follower::create([
        'profile_id' => $actor->profile_id,
        'following_id' => $target->profile_id,
    ]);
    FollowerService::add($actor->profile_id, $target->profile_id);
}

function statusRepliesCreateReply(Status $parent, User $author, string $scope): Status
{
    return Status::factory()->create([
        'profile_id' => $author->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $parent->profile_id,
        'scope' => $scope,
        'visibility' => $scope,
    ]);
}

function statusRepliesIds($response): array
{
    return collect($response->assertOk()->json('data'))->pluck('id')->all();
}

beforeEach(function () {
    $this->author = statusRepliesUser();
    $this->replier = statusRepliesUser();
    $this->viewer = statusRepliesUser();

    $this->post = Status::factory()->create([
        'profile_id' => $this->author->profile_id,
    ]);
    $this->url = "/api/v2/statuses/{$this->post->id}/replies";
});

it('requires a signed-in user', function () {
    $this->getJson($this->url)->assertForbidden();
});

it('returns 404 for a private post the viewer does not follow', function () {
    $private = Status::factory()->private()->create([
        'profile_id' => $this->author->profile_id,
    ]);
    statusRepliesCreateReply($private, $this->replier, 'public');

    $this->actingAs($this->viewer)
        ->getJson("/api/v2/statuses/{$private->id}/replies")
        ->assertNotFound();
});

it('lists replies on a private post to a follower of its author', function () {
    statusRepliesFollow($this->viewer, $this->author);
    $private = Status::factory()->private()->create([
        'profile_id' => $this->author->profile_id,
    ]);
    $reply = statusRepliesCreateReply($private, $this->replier, 'public');

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson("/api/v2/statuses/{$private->id}/replies"));

    expect($ids)->toContain((string) $reply->id);
});

it('hides a followers-only reply from a follower of the parent author who does not follow the replier', function () {
    statusRepliesFollow($this->viewer, $this->author);
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'private');

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->not->toContain((string) $reply->id);
});

it('shows a followers-only reply to a follower of the replier', function () {
    statusRepliesFollow($this->viewer, $this->replier);
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'private');

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->toContain((string) $reply->id);
});

it('shows a followers-only reply to the parent author', function () {
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'private');

    $ids = statusRepliesIds($this->actingAs($this->author)->getJson($this->url));

    expect($ids)->toContain((string) $reply->id);
});

it('never lists direct replies', function () {
    statusRepliesFollow($this->viewer, $this->replier);
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'direct');

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->not->toContain((string) $reply->id);
});

it('hides replies from accounts the viewer blocks', function () {
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'public');

    UserFilter::create([
        'user_id' => $this->viewer->profile_id,
        'filterable_id' => $this->replier->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
    ]);
    UserFilterService::block($this->viewer->profile_id, $this->replier->profile_id);

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->not->toContain((string) $reply->id);
});

it('lists public replies to any signed-in user', function () {
    $reply = statusRepliesCreateReply($this->post, $this->replier, 'public');

    $ids = statusRepliesIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->toContain((string) $reply->id);
});
