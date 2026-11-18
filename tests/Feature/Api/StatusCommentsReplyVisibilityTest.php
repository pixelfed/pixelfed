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
| Followers-only replies on the legacy comments endpoint
|--------------------------------------------------------------------------
|
| GET /api/v2/comments/{profile}/status/{post} decided whether to list
| followers-only replies from the viewer's relation to the parent post's
| author, so anyone following the parent author could read followers-only
| replies from accounts they do not follow. Replies must follow the same
| rule as the status context endpoints.
|
*/

function commentsVisibilityUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function commentsVisibilityFollow(User $actor, User $target): void
{
    Follower::create([
        'profile_id' => $actor->profile_id,
        'following_id' => $target->profile_id,
    ]);
    FollowerService::add($actor->profile_id, $target->profile_id);
}

function commentsVisibilityReplyIds($response): array
{
    return collect($response->assertOk()->json('data'))->pluck('id')->all();
}

beforeEach(function () {
    $this->author = commentsVisibilityUser();
    $this->replier = commentsVisibilityUser();
    $this->viewer = commentsVisibilityUser();

    $this->post = Status::factory()->create([
        'profile_id' => $this->author->profile_id,
    ]);

    $this->privateReply = Status::factory()->private()->create([
        'profile_id' => $this->replier->profile_id,
        'in_reply_to_id' => $this->post->id,
        'in_reply_to_profile_id' => $this->author->profile_id,
    ]);

    $this->url = "/api/v2/comments/{$this->author->profile_id}/status/{$this->post->id}";
});

it('hides a followers-only reply from a follower of the parent author who does not follow the replier', function () {
    commentsVisibilityFollow($this->viewer, $this->author);

    $ids = commentsVisibilityReplyIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->not->toContain((string) $this->privateReply->id);
});

it('shows a followers-only reply to a follower of the replier', function () {
    commentsVisibilityFollow($this->viewer, $this->replier);

    $ids = commentsVisibilityReplyIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->toContain((string) $this->privateReply->id);
});

it('shows a followers-only reply to the parent author', function () {
    $ids = commentsVisibilityReplyIds($this->actingAs($this->author)->getJson($this->url));

    expect($ids)->toContain((string) $this->privateReply->id);
});

it('shows a followers-only reply to its own author', function () {
    $ids = commentsVisibilityReplyIds($this->actingAs($this->replier)->getJson($this->url));

    expect($ids)->toContain((string) $this->privateReply->id);
});

it('hides followers-only replies from guests', function () {
    $ids = commentsVisibilityReplyIds($this->getJson($this->url));

    expect($ids)->not->toContain((string) $this->privateReply->id);
});

it('hides replies from accounts the viewer blocks', function () {
    $publicReply = Status::factory()->create([
        'profile_id' => $this->replier->profile_id,
        'in_reply_to_id' => $this->post->id,
        'in_reply_to_profile_id' => $this->author->profile_id,
    ]);

    UserFilter::create([
        'user_id' => $this->viewer->profile_id,
        'filterable_id' => $this->replier->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
    ]);
    UserFilterService::block($this->viewer->profile_id, $this->replier->profile_id);

    $ids = commentsVisibilityReplyIds($this->actingAs($this->viewer)->getJson($this->url));

    expect($ids)->not->toContain((string) $publicReply->id);
});
