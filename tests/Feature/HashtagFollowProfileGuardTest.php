<?php

use App\Models\Hashtag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| HashtagFollowController profile guard
|--------------------------------------------------------------------------
|
| store() dereferences $user->profile->id when calling HashtagService. A user
| whose profile has been soft-deleted (e.g. via admin account deletion) keeps a
| live session but has a null profile relationship, so the endpoint must fail
| deterministically (422) instead of throwing a 500.
|
*/

it('follows a hashtag for a normal authenticated user', function () {
    $user = User::factory()->create();
    $user->refresh();

    Hashtag::create(['name' => 'landscape', 'slug' => 'landscape']);

    $this->actingAs($user)
        ->postJson('/api/local/discover/tag/subscribe', ['name' => 'landscape'])
        ->assertOk()
        ->assertJson(['state' => 'created']);
});

it('returns 422 instead of 500 when the profile is soft-deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    Hashtag::create(['name' => 'landscape', 'slug' => 'landscape']);

    // Soft-delete the profile, as the admin account-deletion pipeline does,
    // while leaving the still-authenticated session intact. Unset the cached
    // relation so the controller re-queries and sees null, matching what a
    // fresh request resolves after the pipeline runs in another request.
    App\Models\Profile::whereUserId($user->id)->delete();
    $user->unsetRelation('profile');

    $this->actingAs($user)
        ->postJson('/api/local/discover/tag/subscribe', ['name' => 'landscape'])
        ->assertStatus(422);
});
