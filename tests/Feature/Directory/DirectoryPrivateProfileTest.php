<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Public directory excludes private profiles
|--------------------------------------------------------------------------
|
| The directory API lists suggestable local profiles. Private accounts must
| never appear, even if is_suggestable is (stale) true.
|
*/

beforeEach(function () {
    config(['instance.landing.show_directory' => true]);
    config(['instance.enable_cc' => false]);
});

it('excludes private suggestable profiles from the directory', function () {
    $publicUser = User::factory()->create();
    $publicUser->refresh();
    $publicProfile = $publicUser->profile;
    $publicProfile->is_private = false;
    $publicProfile->is_suggestable = true;
    $publicProfile->save();

    $privateUser = User::factory()->create();
    $privateUser->refresh();
    $privateProfile = $privateUser->profile;
    $privateProfile->is_private = true;
    $privateProfile->is_suggestable = true; // stale bug state
    $privateProfile->save();

    $res = $this->getJson('/api/landing/v1/directory')
        ->assertOk()
        ->json('data');

    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();

    expect($ids)->toContain((string) $publicProfile->id);
    expect($ids)->not->toContain((string) $privateProfile->id);
});

it('clears is_suggestable when an account goes private', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->is_suggestable = true;
    $profile->save();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/privacy/account', ['mode' => 'keep-all', 'duration' => 60])
        ->assertOk();

    $fresh = $profile->fresh();
    expect((bool) $fresh->is_private)->toBeTrue();
    expect((bool) $fresh->is_suggestable)->toBeFalse();
});
