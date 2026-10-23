<?php

use App\Models\MediaBlocklist;
use App\Services\MediaBlocklistService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MediaBlocklistService lookups
|--------------------------------------------------------------------------
|
| exists() must use a targeted indexed query (only active hashes), and
| remove() must delete a hash regardless of its active state (previously it
| early-returned via exists(), so inactive hashes could never be removed).
|
*/

function makeBlocklistHash(string $sha256, bool $active): MediaBlocklist
{
    $m = new MediaBlocklist;
    $m->sha256 = $sha256;
    $m->active = $active;
    $m->save();

    return $m;
}

it('reports an active hash as existing', function () {
    makeBlocklistHash('aaaa1111', true);

    expect(MediaBlocklistService::exists('aaaa1111'))->toBeTrue();
});

it('does not report an inactive hash as existing', function () {
    makeBlocklistHash('bbbb2222', false);

    expect(MediaBlocklistService::exists('bbbb2222'))->toBeFalse();
});

it('reports an unknown hash as not existing', function () {
    expect(MediaBlocklistService::exists('cccc3333'))->toBeFalse();
});

it('removes an inactive hash that exists() would not match', function () {
    makeBlocklistHash('dddd4444', false);

    MediaBlocklistService::remove('dddd4444');

    expect(MediaBlocklist::whereSha256('dddd4444')->exists())->toBeFalse();
});

it('removes an active hash', function () {
    makeBlocklistHash('eeee5555', true);

    MediaBlocklistService::remove('eeee5555');

    expect(MediaBlocklist::whereSha256('eeee5555')->exists())->toBeFalse();
});
