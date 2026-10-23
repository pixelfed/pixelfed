<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Privacy save must preserve crawlable while the account is private
|--------------------------------------------------------------------------
|
| When the account is private the "Disable search engine indexing" checkbox
| is rendered disabled, so it is omitted from the POST. The controller must
| not treat the omission as "unchecked" and flip the user's opt-out back to
| crawlable=true.
|
*/

it('preserves crawlable=false when saving privacy while account is private', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Opted out of search-engine indexing while public.
    $user->settings->update(['crawlable' => false]);
    $user->settings->refresh();
    expect((bool) $user->settings->crawlable)->toBeFalse();

    // Account goes private; the crawlable checkbox is now disabled.
    $user->profile->update(['is_private' => true]);

    $this->actingAs($user)
        ->post('/settings/privacy', [])
        ->assertRedirect();

    $user->settings->refresh();
    expect((bool) $user->settings->crawlable)->toBeFalse();
});

it('still toggles crawlable when the account is public', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->settings->update(['crawlable' => false]);
    $user->profile->update(['is_private' => false]);

    // Public save with the checkbox unchecked (crawlable absent) allows indexing.
    $this->actingAs($user)
        ->post('/settings/privacy', [])
        ->assertRedirect();

    $user->settings->refresh();
    expect((bool) $user->settings->crawlable)->toBeTrue();
});
