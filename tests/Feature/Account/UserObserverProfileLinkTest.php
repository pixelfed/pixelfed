<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| UserObserver profile linking
|--------------------------------------------------------------------------
|
| A User must always end up with a linked profile_id. Previously profile
| creation and the profile_id update were separate transactions, and the
| observer bailed out early whenever a profile with the username existed —
| so if the link step ever failed, the user was stuck with a null profile_id
| forever. The observer must create+link atomically and recover an orphaned
| (unlinked) profile on a later run.
|
*/

it('links a newly created user to its profile', function () {
    $user = User::factory()->create();
    $user->refresh();

    expect($user->profile_id)->not->toBeNull();
    expect($user->profile)->not->toBeNull();
    expect($user->profile->user_id)->toBe($user->id);
});

it('recovers a user left with a null profile_id but an existing profile', function () {
    $user = User::factory()->create();
    $user->refresh();

    $profileId = $user->profile_id;
    expect($profileId)->not->toBeNull();

    // Simulate the partial-failure state: the profile row exists (user_id set)
    // but users.profile_id was never persisted. Break it directly, bypassing
    // the observer.
    DB::table('users')->where('id', $user->id)->update(['profile_id' => null]);
    expect(User::find($user->id)->profile_id)->toBeNull();

    // A later save fires the observer, which must adopt the orphaned profile.
    $broken = User::find($user->id);
    $broken->save();

    $fixed = User::find($user->id);
    expect($fixed->profile_id)->toBe($profileId);
    expect(Profile::whereUserId($user->id)->count())->toBe(1);
});
