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

/*
|--------------------------------------------------------------------------
| Bulk display-name corpus
|--------------------------------------------------------------------------
|
| ~50 names exercising the sanitize pipeline end-to-end through
| PATCH /api/v1/accounts/update_credentials. Every input is <= 30 raw chars
| so it passes the display_name max:30 validation and reaches the sanitizer.
| Expected outputs were computed against the live HTMLPurifier config.
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

// Legitimate names: must round-trip unchanged (no entity encoding, no loss).
it('preserves legitimate display names verbatim', function (string $name) {
    expect(updateDisplayName($name))->toBe($name);
})->with([
    'plain ascii' => ['Alice'],
    'two words' => ['Bob Smith'],
    'ampersand pair' => ['Tom & Jerry'],
    'ampersand pair 2' => ['Ben & Jerry'],
    'rock and roll' => ['Rock & Roll'],
    'brand AT&T' => ['AT&T'],
    'r and d' => ['R&D Team'],
    'brand P&G' => ['P&G'],
    'brand M&Ms' => ['M&Ms'],
    'multi ampersand' => ['a & b & c'],
    'diacritic zoe' => ['Zoë Müller'],
    'diacritic jose' => ['José'],
    'diacritic francois' => ['François'],
    'diacritic renee' => ['Renée'],
    'accented ampersand' => ['café & thé'],
    'nordic soren' => ['Søren'],
    'naive' => ['naïve'],
    'cjk chinese' => ['北京老王'],
    'cjk japanese' => ['山田太郎'],
    'arabic' => ['علي'],
    'cyrillic' => ['Даша'],
    'greek' => ['Σωκράτης'],
    'korean' => ['김민준'],
    'emoji flower' => ['🌸 flower'],
    'emoji cat' => ['cat 🐈 lover'],
    'emoji inline' => ['emoji😀name'],
    'cpp dev' => ['C++ Dev'],
    'gt symbol' => ['5 > 3'],
    'lt symbol' => ['2 < 4'],
    'percent' => ['100% cotton'],
    'dotted' => ['plain.name'],
    'underscore' => ['under_score'],
    'dashed' => ['dash-name'],
    'quoted' => ['"Quoted"'],
    'apostrophe' => ["It's me"],
    'sql-looking but plain' => ["'; DROP TABLE u;--"],
]);

// Dirty-but-recoverable: tags stripped, entities decoded, exact readable output.
it('sanitizes dirty display names to the expected readable text', function (string $name, string $expected) {
    expect(updateDisplayName($name))->toBe($expected);
})->with([
    'bold tag stripped' => ['<b>Bold</b>', 'Bold'],
    'bold around name' => ['Tom & <b>J</b>', 'Tom & J'],
    'italic prefix' => ['<i>x</i>Safe', 'xSafe'],
    'tag mid-string' => ['a<b>c', 'ac'],
    'script stripped keeps tail' => ['<script>x</script>Y', 'Y'],
    'style stripped keeps tail' => ['<style>q</style>W', 'W'],
    'div handler stripped' => ['<div onclick=x>d</div>', 'd'],
    'pre-encoded lt gt decoded' => ['&lt;script&gt;', '<script>'],
    'pre-encoded amp decoded' => ['&amp;pre', '&pre'],
    'nested tags' => ['<b><i>hi</i></b>', 'hi'],
    'unknown tag stripped' => ['<xyz>tag', 'tag'],
]);

// Dangerous inputs: whatever survives must carry no executable markup.
it('never stores executable markup', function (string $name) {
    $stored = strtolower(updateDisplayName($name));

    foreach (['<script', '</script', 'onerror', 'onload', 'onclick', '<img', '<svg', '<iframe', 'javascript:'] as $marker) {
        expect($stored)->not->toContain($marker);
    }
})->with([
    'script tag' => ['<script>x</script>Y'],
    'img onerror' => ['<img src=x onerror=y>'],
    'svg onload' => ['<svg onload=z>'],
    'div onclick' => ['<div onclick=x>d</div>'],
    'style tag' => ['<style>q</style>W'],
    'nested markup' => ['<b><i>hi</i></b>'],
    'stray open tag' => ['  <b>  '],
]);
