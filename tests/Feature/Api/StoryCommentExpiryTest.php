<?php

use App\Models\Follower;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Stories API comment must respect story expiry
|--------------------------------------------------------------------------
|
| StoryApiV1Controller::comment looked up the story with a bare findOrFail,
| so a follower could keep replying to a long-expired story. It must reject
| expired/inactive stories with 404, matching the web comment path and the
| sibling viewed() endpoint.
|
*/

beforeEach(function () {
    config(['instance.stories.enabled' => true]);
    config(['instance.enable_cc' => false]);
});

function makeStory(int $profileId, bool $active, $expiresAt): Story
{
    $story = new Story;
    $story->profile_id = $profileId;
    $story->duration = 3;
    $story->type = 'photo';
    $story->mime = 'image/jpeg';
    $story->path = 'public/story/'.$profileId.'/example.jpg';
    $story->local = true;
    $story->active = $active;
    $story->can_reply = true;
    $story->expires_at = $expiresAt;
    $story->save();

    return $story;
}

it('rejects a comment on an expired story with 404', function () {
    $author = User::factory()->create();
    $author->refresh();
    $follower = User::factory()->create();
    $follower->refresh();

    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $author->profile_id,
    ]);

    $story = makeStory($author->profile_id, false, now()->subHours(2));

    Passport::actingAs($follower, ['read', 'write']);

    $this->postJson('/api/v1.1/stories/comment', [
        'sid' => $story->id,
        'caption' => 'late reply',
    ])->assertStatus(404);
});

it('lets an active, unexpired story past the expiry guard', function () {
    $author = User::factory()->create();
    $author->refresh();
    $follower = User::factory()->create();
    $follower->refresh();

    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $author->profile_id,
    ]);

    // can_reply=false so the request reaches the can_reply check (422) rather
    // than creating a Status. Proves the active story passed the expiry guard
    // (i.e. was not rejected with 404).
    $story = makeStory($author->profile_id, true, now()->addHours(6));
    $story->can_reply = false;
    $story->save();

    Passport::actingAs($follower, ['read', 'write']);

    $this->postJson('/api/v1.1/stories/comment', [
        'sid' => $story->id,
        'caption' => 'nice story',
    ])->assertStatus(422);
});
