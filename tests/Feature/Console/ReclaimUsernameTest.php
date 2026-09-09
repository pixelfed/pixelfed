<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:reclaim-username
|--------------------------------------------------------------------------
|
| Reclaiming a deleted user's username must force-delete the user's OWN
| profile(s) (scoped by user_id) and must not report success while a
| same-username orphan profile still claims the username.
|
*/

/**
 * Mark a user as deleted so the reclaim gate (status/delete_after) passes.
 */
function markDeleted(User $user): void
{
    $user->status = 'deleted';
    $user->delete_after = now()->subDay();
    $user->save();
}

it('force-deletes only the user\'s own profile, not a same-username orphan, and fails when the orphan survives', function () {
    $target = User::factory()->create(['username' => 'reclaimme']);
    $target->refresh();
    $ownProfileId = $target->profile_id;

    markDeleted($target);

    // A live orphan profile with the same username but NOT linked to $target.
    // (domain,username) unique permits this because domain is NULL.
    $orphan = Profile::factory()->create([
        'username' => 'reclaimme',
        'user_id' => null,
    ]);

    $this->artisan('app:reclaim-username')
        ->expectsSearch(
            'What username would you like to reclaim?',
            answer: 'reclaimme',
            search: 'reclaimme',
            answers: ['reclaimme'],
        )
        ->expectsConfirmation(
            'Are you sure you want to force delete user and profile with username: reclaimme?',
            'yes'
        )
        ->assertExitCode(1);

    // The orphan must survive: we only delete the user's own profile.
    expect(Profile::whereId($orphan->id)->exists())->toBeTrue();
    // The user's own profile is gone (force-deleted, scoped by user_id).
    expect(Profile::whereId($ownProfileId)->withTrashed()->exists())->toBeFalse();
});

it('reclaims cleanly and reports success when no orphan survives', function () {
    $target = User::factory()->create(['username' => 'soloname']);
    $target->refresh();
    $ownProfileId = $target->profile_id;

    markDeleted($target);

    $this->artisan('app:reclaim-username')
        ->expectsSearch(
            'What username would you like to reclaim?',
            answer: 'soloname',
            search: 'soloname',
            answers: ['soloname'],
        )
        ->expectsConfirmation(
            'Are you sure you want to force delete user and profile with username: soloname?',
            'yes'
        )
        ->expectsOutputToContain('Username reclaimed successfully!')
        ->assertExitCode(0);

    expect(Profile::whereUsername('soloname')->withTrashed()->exists())->toBeFalse();
    expect(User::whereId($target->id)->withTrashed()->exists())->toBeFalse();
    expect($ownProfileId)->not->toBeNull();
});
