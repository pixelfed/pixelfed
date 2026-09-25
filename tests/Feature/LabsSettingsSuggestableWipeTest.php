<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Labs settings must not touch the Privacy "Show on Directory" preference
|--------------------------------------------------------------------------
|
| The Labs form previously carried dead profile_suggestions handling whose
| else-branch forced profiles.is_suggestable = false on every save, silently
| reverting the user's Privacy directory opt-in. Dark mode (the only live
| control) is now handled elsewhere, so Labs no longer writes to the profile.
|
*/

beforeEach(function () {
    config(['instance.landing.show_directory' => true]);
    config(['instance.enable_cc' => false]);
});

it('does not clear is_suggestable when the labs form is saved', function () {
    $user = User::factory()->create();
    $user->refresh();

    $profile = $user->profile;
    $profile->is_suggestable = true;
    $profile->save();

    expect((bool) $profile->fresh()->is_suggestable)->toBeTrue();

    $this->actingAs($user)
        ->post('/settings/labs', [])
        ->assertRedirect(route('settings.labs'));

    expect((bool) $profile->fresh()->is_suggestable)
        ->toBeTrue('saving Labs must not revert the Privacy directory opt-in');
});

it('keeps the profile in the guest landing-page directory after a labs save', function () {
    $user = User::factory()->create();
    $user->refresh();

    $profile = $user->profile;
    $profile->is_private = false;
    $profile->is_suggestable = true;
    $profile->save();

    $before = $this->getJson('/api/landing/v1/directory')->assertOk()->json('data');
    $idsBefore = collect($before)->pluck('id')->map(fn ($id) => (string) $id)->all();
    expect($idsBefore)->toContain((string) $profile->id);

    $this->actingAs($user)
        ->post('/settings/labs', [])
        ->assertRedirect(route('settings.labs'));

    $after = $this->getJson('/api/landing/v1/directory')->assertOk()->json('data');
    $idsAfter = collect($after)->pluck('id')->map(fn ($id) => (string) $id)->all();
    expect($idsAfter)->toContain((string) $profile->id);
});
