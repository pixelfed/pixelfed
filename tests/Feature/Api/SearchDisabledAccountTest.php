<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v2/search account visibility
|--------------------------------------------------------------------------
|
| Account search must exclude self-deactivated profiles (status = 'disabled'),
| matching every profile-show/lookup path. The listing query had dropped its
| whereNull('status') filter, so a disabled account was discoverable by
| partial username and leaked its full AccountTransformer payload.
|
*/

function searchAccounts(object $test, User $viewer, string $q): array
{
    Passport::actingAs($viewer, ['read']);

    return $test->getJson("/api/v2/search?q={$q}&type=accounts")
        ->assertOk()
        ->json('accounts');
}

it('does not return a self-disabled account in search results', function () {
    $target = User::factory()->create(['username' => 'ghosted']);
    $target->refresh();

    // Self-service temporary disable sets profiles.status = 'disabled'.
    $profile = $target->profile;
    $profile->status = 'disabled';
    $profile->save();

    $viewer = User::factory()->create();
    $viewer->refresh();

    $usernames = collect(searchAccounts($this, $viewer, 'ghosted'))->pluck('username');

    expect($usernames)->not->toContain('ghosted');
});

it('still returns an active account by partial username', function () {
    $target = User::factory()->create(['username' => 'visibleuser']);
    $target->refresh();

    $viewer = User::factory()->create();
    $viewer->refresh();

    $usernames = collect(searchAccounts($this, $viewer, 'visible'))->pluck('username');

    expect($usernames)->toContain('visibleuser');
});

it('excludes a disabled account matched via the webfinger branch', function () {
    // Guards the OR-precedence hazard: the status filter must apply to the
    // webfinger branch too, not just the username branch.
    $target = User::factory()->create(['username' => 'wfuser']);
    $target->refresh();

    $profile = $target->profile;
    $profile->status = 'disabled';
    $profile->save();

    $viewer = User::factory()->create();
    $viewer->refresh();

    $usernames = collect(searchAccounts($this, $viewer, 'wfuser'))->pluck('username');

    expect($usernames)->not->toContain('wfuser');
});
