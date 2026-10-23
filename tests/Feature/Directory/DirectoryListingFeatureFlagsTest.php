<?php

use App\Http\Controllers\PixelfedDirectoryController;
use App\Models\ConfigCache;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Directory listing feature flags
|--------------------------------------------------------------------------
|
| buildListing() must report oauth_enabled / activitypub_enabled from the
| stored config VALUE. Casting the ConfigCache model to bool always yields
| true, forcing both flags on even when the admin disabled them.
|
*/

/**
 * Persist a config_cache row so the (pre-fix) model lookup finds a row,
 * reproducing the production condition where config_cache auto-creates rows.
 */
function setConfig(string $key, $value): void
{
    config([$key => $value]);
    ConfigCache::updateOrCreate(['k' => $key], ['v' => $value === false ? '' : (string) $value]);
}

beforeEach(function () {
    // buildListing() reads a base listing and a summary; provide both so the
    // method reaches the feature-flag logic under test.
    config(['pixelfed.directory' => ['summary' => 'Test instance']]);
    ConfigCache::updateOrCreate(['k' => 'app.short_description'], ['v' => 'Test instance']);
});

it('reports disabled feature flags as false', function () {
    setConfig('federation.activitypub.enabled', false);
    setConfig('pixelfed.oauth_enabled', false);

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['activitypub_enabled'])->toBeFalse()
        ->and($res['oauth_enabled'])->toBeFalse();
});

it('reports enabled feature flags as true when configured and keys present', function () {
    setConfig('federation.activitypub.enabled', true);
    setConfig('pixelfed.oauth_enabled', true);
    // Satisfy the oauth key presence check without touching the filesystem.
    config(['passport.public_key' => 'test-public-key']);
    config(['passport.private_key' => 'test-private-key']);

    $res = (new PixelfedDirectoryController)->buildListing();

    expect($res['activitypub_enabled'])->toBeTrue()
        ->and($res['oauth_enabled'])->toBeTrue();
});
