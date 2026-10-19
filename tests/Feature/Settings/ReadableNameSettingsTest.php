<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Settings name is stored readable
|--------------------------------------------------------------------------
|
| The /settings/home name field keeps the value as entered. Symbols like &,
| < and > are stored verbatim (no entity encoding, no tag stripping). Only
| control characters are removed and the result is trimmed.
|
*/

beforeEach(function () {
    config(['pixelfed.enforce_email_verification' => false]);
});

function updateSettingsName(string $name): string
{
    $user = User::factory()->create();
    $user->refresh();

    test()->actingAs($user)
        ->post('/settings/home', ['name' => $name])
        ->assertRedirect('/settings/home');

    $user->profile->refresh();

    return (string) $user->profile->name;
}

describe('readable settings name', function () {
    it('stores readable symbols verbatim on the profile', function (string $name) {
        expect(updateSettingsName($name))->toBe($name);
    })->with([
        'ampersand words' => ['Tom & Jerry'],
        'brand AT&T' => ['AT&T'],
    ]);

    it('does not strip angle-bracket text', function () {
        expect(updateSettingsName('Tom & <b>Jerry</b>'))->toBe('Tom & <b>Jerry</b>');
    });

    it('removes control characters but keeps the visible text', function () {
        expect(updateSettingsName("Ann\x00e\x7F Lee"))->toBe('Anne Lee');
    });

    it('trims leading and trailing whitespace', function () {
        expect(updateSettingsName('  Spaced Out  '))->toBe('Spaced Out');
    });
});
