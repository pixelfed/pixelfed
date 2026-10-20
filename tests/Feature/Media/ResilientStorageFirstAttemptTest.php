<?php

use App\Services\ResilientMediaStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Resilient store first-attempt disk routing
|--------------------------------------------------------------------------
|
| The first attempt of every store must target the configured cloud disk.
| Alternate disks are only a retry fallback. Two back-to-back stores must
| both land on the configured cloud disk, never on an alternate.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    Config::set('pixelfed.cloud_storage', true);
    Config::set('media.storage.remote.resilient_mode', true);
    Config::set('filesystems.disks.alt-primary.enabled', true);
    Config::set('filesystems.disks.alt-secondary.enabled', true);

    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://cdn.test']);
    Storage::fake('alt-primary', ['url' => 'https://alt.test']);
    Storage::fake('alt-secondary', ['url' => 'https://alt2.test']);
});

function makeLocalSourceForResilientFirstAttempt(string $name, string $contents): string
{
    $dir = sys_get_temp_dir().'/pf-resilient-first-'.uniqid();
    mkdir($dir);
    $file = $dir.'/'.$name;
    file_put_contents($file, $contents);

    return $file;
}

it('routes both successive first attempts to the configured cloud disk', function () {
    $storagePath = 'public/m/_v2/1/aa/bb';

    $firstSource = makeLocalSourceForResilientFirstAttempt('one.jpg', 'FIRST-BYTES-0001');
    $secondSource = makeLocalSourceForResilientFirstAttempt('two.jpg', 'SECOND-BYTES-0002');

    ResilientMediaStorageService::store($storagePath, $firstSource, 'one.jpg');
    ResilientMediaStorageService::store($storagePath, $secondSource, 'two.jpg');

    expect(Storage::disk('s3')->exists($storagePath.'/one.jpg'))->toBeTrue();
    expect(Storage::disk('s3')->exists($storagePath.'/two.jpg'))->toBeTrue();

    expect(Storage::disk('alt-primary')->exists($storagePath.'/one.jpg'))->toBeFalse();
    expect(Storage::disk('alt-primary')->exists($storagePath.'/two.jpg'))->toBeFalse();
    expect(Storage::disk('alt-secondary')->exists($storagePath.'/one.jpg'))->toBeFalse();
    expect(Storage::disk('alt-secondary')->exists($storagePath.'/two.jpg'))->toBeFalse();
});
