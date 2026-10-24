<?php

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Web hashtag-follow endpoint must enforce MAX_LIMIT on the create branch
|--------------------------------------------------------------------------
|
| POST /api/local/discover/tag/subscribe is a follow/unfollow toggle that did
| not enforce HashtagFollow::MAX_LIMIT, unlike the OAuth sibling. The cap must
| apply to new follows but never block unfollow toggles at the cap.
|
*/

function seedFollows(User $user, int $count): array
{
    $tags = [];
    for ($i = 0; $i < HashtagFollow::MAX_LIMIT + 1; $i++) {
        $tags[] = Hashtag::create(['name' => 'tag'.$i, 'slug' => 'tag'.$i]);
    }
    for ($i = 0; $i < $count; $i++) {
        HashtagFollow::create([
            'user_id' => $user->id,
            'profile_id' => $user->profile_id,
            'hashtag_id' => $tags[$i]->id,
        ]);
    }

    return $tags;
}

it('rejects following past MAX_LIMIT via the web endpoint', function () {
    $user = User::factory()->create();
    $user->refresh();

    $tags = seedFollows($user, HashtagFollow::MAX_LIMIT);
    expect(HashtagFollow::whereProfileId($user->profile_id)->count())->toBe(HashtagFollow::MAX_LIMIT);

    $this->actingAs($user)
        ->postJson('/api/local/discover/tag/subscribe', ['name' => $tags[HashtagFollow::MAX_LIMIT]->name])
        ->assertStatus(422);

    expect(HashtagFollow::whereProfileId($user->profile_id)->count())->toBe(HashtagFollow::MAX_LIMIT);
});

it('still allows unfollowing when at the cap', function () {
    $user = User::factory()->create();
    $user->refresh();

    $tags = seedFollows($user, HashtagFollow::MAX_LIMIT);

    // Toggling an already-followed tag off must succeed even at the cap.
    $this->actingAs($user)
        ->postJson('/api/local/discover/tag/subscribe', ['name' => $tags[0]->name])
        ->assertOk()
        ->assertJson(['state' => 'deleted']);

    expect(HashtagFollow::whereProfileId($user->profile_id)->count())->toBe(HashtagFollow::MAX_LIMIT - 1);
});

it('allows a new follow below the cap', function () {
    $user = User::factory()->create();
    $user->refresh();

    $tags = seedFollows($user, HashtagFollow::MAX_LIMIT - 1);

    $this->actingAs($user)
        ->postJson('/api/local/discover/tag/subscribe', ['name' => $tags[HashtagFollow::MAX_LIMIT - 1]->name])
        ->assertOk()
        ->assertJson(['state' => 'created']);

    expect(HashtagFollow::whereProfileId($user->profile_id)->count())->toBe(HashtagFollow::MAX_LIMIT);
});
