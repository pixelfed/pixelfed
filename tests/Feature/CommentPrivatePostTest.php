<?php

use App\Follower;
use App\Services\FollowerService;
use App\Status;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Comment on Private Post - Access Control Tests
|--------------------------------------------------------------------------
|
| Regression tests for the FollowerService import in CommentController.
| Ensures that non-followers are denied and followers are allowed to
| comment on private posts via POST /i/comment.
|
*/

describe('commenting on private posts', function () {
    it('denies non-follower from commenting on a private post', function () {
        $owner = User::factory()->create();
        $owner->refresh();

        $commenter = User::factory()->create();
        $commenter->refresh();

        $status = Status::factory()->private()->create([
            'profile_id' => $owner->profile->id,
        ]);

        $this->actingAs($commenter)
            ->post('/i/comment', [
                'item' => $status->id,
                'comment' => 'This should be denied',
            ])
            ->assertNotFound();
    });

    it('allows a follower to comment on a private post', function () {
        $owner = User::factory()->create();
        $owner->refresh();

        $commenter = User::factory()->create();
        $commenter->refresh();

        Follower::create([
            'profile_id' => $commenter->profile->id,
            'following_id' => $owner->profile->id,
        ]);

        FollowerService::add($commenter->profile->id, $owner->profile->id);

        $status = Status::factory()->private()->create([
            'profile_id' => $owner->profile->id,
        ]);

        $this->actingAs($commenter)
            ->post('/i/comment', [
                'item' => $status->id,
                'comment' => 'This should be allowed',
            ])
            ->assertStatus(302);
    });

    it('allows the post owner to comment on their own private post', function () {
        $owner = User::factory()->create();
        $owner->refresh();

        $status = Status::factory()->private()->create([
            'profile_id' => $owner->profile->id,
        ]);

        $this->actingAs($owner)
            ->post('/i/comment', [
                'item' => $status->id,
                'comment' => 'Owner commenting on own post',
            ])
            ->assertStatus(302);
    });

    it('denies unauthenticated users from commenting', function () {
        $owner = User::factory()->create();
        $owner->refresh();

        $status = Status::factory()->private()->create([
            'profile_id' => $owner->profile->id,
        ]);

        $this->post('/i/comment', [
            'item' => $status->id,
            'comment' => 'Anonymous comment',
        ])->assertForbidden();
    });
});
