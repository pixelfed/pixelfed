<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Display name is stored readable
|--------------------------------------------------------------------------
|
| The display name is kept as the user typed it. Symbols like &, < and > are
| stored verbatim (no entity encoding, no tag stripping) so a name reads back
| the way it was entered. Only control characters are removed and the result
| is trimmed.
|
*/

function updateDisplayName(string $name): string
{
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write']);

    test()->patchJson('/api/v1/accounts/update_credentials', [
        'display_name' => $name,
    ])->assertOk();

    return (string) $user->fresh()->name;
}

describe('readable display name', function () {
    it('stores readable symbols verbatim on both the user and profile', function (string $name) {
        $user = User::factory()->create();
        $user->refresh();

        Passport::actingAs($user, ['write']);

        $this->patchJson('/api/v1/accounts/update_credentials', [
            'display_name' => $name,
        ])->assertOk();

        $user->refresh();
        expect($user->name)->toBe($name)
            ->and(Profile::find($user->profile_id)->name)->toBe($name);
    })->with([
        'ampersand words' => ['Tom & Jerry'],
        'brand AT&T' => ['AT&T'],
        'greater than' => ['5 > 3'],
        'less than' => ['2 < 4'],
    ]);

    it('does not strip angle-bracket text', function () {
        expect(updateDisplayName('Tom & <b>Jerry</b>'))->toBe('Tom & <b>Jerry</b>');
    });

    it('removes control characters but keeps the visible text', function () {
        expect(updateDisplayName("Ann\x00e\x1F \x7FLee"))->toBe('Anne Lee');
    });

    it('trims leading and trailing whitespace', function () {
        expect(updateDisplayName('  Spaced Out  '))->toBe('Spaced Out');
    });
});
