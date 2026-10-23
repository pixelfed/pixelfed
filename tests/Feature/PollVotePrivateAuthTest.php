<?php

use App\Models\Follower;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Poll vote authorization
|--------------------------------------------------------------------------
|
| GET /api/v1/polls/{id} gates non-public polls to the owner or a follower.
| POST /api/v1/polls/{id}/votes must enforce the same gate; otherwise an
| authenticated non-follower can vote on (and read back) a private poll they
| are not authorized to see.
|
*/

beforeEach(function () {
    config(['instance.polls.enabled' => true]);
});

/**
 * Create a poll on a status with the given scope, owned by $owner.
 */
function makePoll(User $owner, string $scope): Poll
{
    $status = Status::factory()->create([
        'profile_id' => $owner->profile_id,
        'type' => 'poll',
        'scope' => $scope,
        'visibility' => $scope,
    ]);

    $poll = new Poll;
    $poll->status_id = $status->id;
    $poll->profile_id = $owner->profile_id;
    $poll->poll_options = ['Yes', 'No'];
    $poll->cached_tallies = [0, 0];
    $poll->votes_count = 0;
    $poll->expires_at = now()->addDay();
    $poll->save();

    return $poll;
}

it('denies a non-follower voting on a private poll (matching the read gate)', function () {
    $owner = User::factory()->create();
    $owner->refresh();
    $outsider = User::factory()->create();
    $outsider->refresh();

    $poll = makePoll($owner, 'private');

    // The read side denies the non-follower with 404.
    $this->actingAs($outsider)
        ->getJson("/api/v1/polls/{$poll->id}")
        ->assertNotFound();

    // The write side must deny it the same way.
    $this->actingAs($outsider)
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [0]])
        ->assertNotFound();

    // No vote persisted, no tally mutation.
    expect(PollVote::wherePollId($poll->id)->whereProfileId($outsider->profile_id)->exists())->toBeFalse();
    expect($poll->fresh()->votes_count)->toBe(0);
});

it('denies a non-follower voting on an unlisted poll', function () {
    $owner = User::factory()->create();
    $owner->refresh();
    $outsider = User::factory()->create();
    $outsider->refresh();

    $poll = makePoll($owner, 'unlisted');

    $this->actingAs($outsider)
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [0]])
        ->assertNotFound();

    expect(PollVote::wherePollId($poll->id)->whereProfileId($outsider->profile_id)->exists())->toBeFalse();
});

it('allows a follower to vote on a private poll', function () {
    $owner = User::factory()->create();
    $owner->refresh();
    $follower = User::factory()->create();
    $follower->refresh();

    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $owner->profile_id,
    ]);

    $poll = makePoll($owner, 'private');

    $this->actingAs($follower)
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [0]])
        ->assertOk();

    expect(PollVote::wherePollId($poll->id)->whereProfileId($follower->profile_id)->exists())->toBeTrue();
    expect($poll->fresh()->votes_count)->toBe(1);
});

it('allows the owner to vote on their own private poll', function () {
    $owner = User::factory()->create();
    $owner->refresh();

    $poll = makePoll($owner, 'private');

    $this->actingAs($owner)
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [1]])
        ->assertOk();

    expect(PollVote::wherePollId($poll->id)->whereProfileId($owner->profile_id)->exists())->toBeTrue();
});
