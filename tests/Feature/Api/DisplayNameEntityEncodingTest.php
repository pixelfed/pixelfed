<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Display name must not be HTML-entity encoded
|--------------------------------------------------------------------------
|
| Purify::clean entity-encodes &, <, > even in plain text, and strip_tags
| doesn't decode, so names like "Tom & Jerry" were stored as
| "Tom &amp; Jerry" and became un-self-correctable. The pipeline must decode
| entities after stripping tags, while still removing XSS.
|
*/

it('preserves ampersands in the display name instead of entity-encoding', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'display_name' => 'Tom & Jerry',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Tom & Jerry');
    expect(Profile::find($user->profile_id)->name)->toBe('Tom & Jerry');
});

it('strips tags but keeps surrounding text and ampersands', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'display_name' => 'Tom & <b>Jerry</b>',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Tom & Jerry');
});

it('removes script content (XSS protection preserved)', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'display_name' => '<script>alert(1)</script>Safe',
    ])->assertOk();

    $user->refresh();
    expect($user->name)->not->toContain('<script>');
    expect($user->name)->not->toContain('alert(1)');
    expect($user->name)->toContain('Safe');
});
