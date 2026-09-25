<?php

use App\Http\Controllers\AdminController;
use App\Models\ConfigCache;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;

// Read the persisted config_cache row directly so the assertion does not
// depend on ConfigCacheService::get's enable_cc fallthrough behavior.
function storedCloudStorage(): ?string
{
    return ConfigCache::whereK('pixelfed.cloud_storage')->value('v');
}

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin storage settings must not flip cloud_storage before verification
|--------------------------------------------------------------------------
|
| settingsApiUpdateStorageType flipped pixelfed.cloud_storage=true up front,
| before credential verification / cloud-ready checks, and never rolled it
| back on the failure path. The flag must only flip to true once the cloud
| disk is actually configured.
|
*/

beforeEach(function () {
    config(['instance.enable_cc' => false]);
    config(['filesystems.cloud' => 's3']);
    ConfigCacheService::put('pixelfed.cloud_storage', false);
});

// The endpoint always validates a full disk_config payload (the UI always
// sends it); update_disk being absent skips the credential-write block.
function storageRequest(string $primaryDisk): Request
{
    return Request::create('/', 'POST', [
        'primary_disk' => $primaryDisk,
        'disk_config' => [
            'driver' => 's3',
            'key' => 'AKIAKEY',
            'secret' => 'shhh-secret',
            'region' => 'us-east-1',
            'bucket' => 'bucket',
            'visibility' => 'private',
            'endpoint' => 'https://s3.example.test',
            'url' => 'https://cdn.example.test',
        ],
    ]);
}

it('does not enable cloud_storage when the cloud disk is not configured', function () {
    // s3 disk has no key/secret -> not cloud-ready.
    config(['filesystems.disks.s3.key' => null]);
    config(['filesystems.disks.s3.secret' => null]);

    $request = storageRequest('cloud');

    $response = (new AdminController)->settingsApiUpdateStorageType($request);

    expect($response->getStatusCode())->toBe(400);
    // The flag must NOT have been flipped to true (stays at seeded false).
    expect(filter_var(storedCloudStorage(), FILTER_VALIDATE_BOOLEAN))->toBeFalse();
});

it('enables cloud_storage when the cloud disk is configured', function () {
    config(['filesystems.disks.s3.key' => 'AKIAKEY']);
    config(['filesystems.disks.s3.secret' => 'shhh-secret']);

    $request = storageRequest('cloud');

    (new AdminController)->settingsApiUpdateStorageType($request);

    expect(filter_var(storedCloudStorage(), FILTER_VALIDATE_BOOLEAN))->toBeTrue();
});

it('disables cloud_storage when switching to local', function () {
    ConfigCacheService::put('pixelfed.cloud_storage', true);

    $request = storageRequest('local');

    (new AdminController)->settingsApiUpdateStorageType($request);

    expect(filter_var(storedCloudStorage(), FILTER_VALIDATE_BOOLEAN))->toBeFalse();
});
