<?php

use App\Models\Avatar;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| avatar:storage "Move local avatars to cloud" - regression
|--------------------------------------------------------------------------
|
| CreateAvatar writes is_remote = 0 for local avatars, but the migration
| selected only whereNull('is_remote'), so modern local avatars were never
| uploaded. The selection must include is_remote = 0 (scoped to local
| profiles) as well as legacy NULL rows.
|
*/

beforeEach(function () {
    config(['instance.enable_cc' => false]);
    config(['pixelfed.cloud_storage' => true]);
    config(['instance.avatar.local_to_cloud' => true]);
    config(['filesystems.cloud' => 's3']);
    Storage::fake('local');
    Storage::fake('s3');
    // The command seeds the cloud default from the local default avatar.
    Storage::disk('local')->put('public/avatars/default.jpg', 'default-avatar-bytes');
});

function seedLocalAvatar(int $isRemoteValue): Avatar
{
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->domain = null;
    $profile->save();

    $path = 'public/avatars/'.$profile->id.'/avatar_'.uniqid().'.jpg';
    Storage::disk('local')->put($path, 'fake-image-bytes');

    return Avatar::updateOrCreate(
        ['profile_id' => $profile->id],
        ['media_path' => $path, 'is_remote' => $isRemoteValue, 'cdn_url' => null]
    );
}

it('migrates modern local avatars (is_remote=0) to cloud', function () {
    $avatar = seedLocalAvatar(0);

    $this->artisan('avatar:storage')
        ->expectsChoice('Select action:', 'Move local avatars to cloud', [
            'Cancel',
            'Upload default avatar to cloud',
            'Move local avatars to cloud',
            'Re-fetch remote avatars',
        ])
        ->expectsConfirmation('Are you sure you want to move local avatars to cloud storage?', 'yes')
        ->assertExitCode(0);

    $avatar->refresh();
    expect($avatar->cdn_url)->not->toBeNull('modern local avatar should have been migrated');
    expect($avatar->media_path)->toStartWith('cache/avatars/');
    Storage::disk('s3')->assertExists($avatar->media_path);
});

it('also migrates legacy local avatars (is_remote NULL)', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->domain = null;
    $profile->save();

    $path = 'public/avatars/'.$profile->id.'/legacy.jpg';
    Storage::disk('local')->put($path, 'fake-image-bytes');
    $avatar = Avatar::updateOrCreate(
        ['profile_id' => $profile->id],
        ['media_path' => $path, 'is_remote' => null, 'cdn_url' => null]
    );

    $this->artisan('avatar:storage')
        ->expectsChoice('Select action:', 'Move local avatars to cloud', [
            'Cancel',
            'Upload default avatar to cloud',
            'Move local avatars to cloud',
            'Re-fetch remote avatars',
        ])
        ->expectsConfirmation('Are you sure you want to move local avatars to cloud storage?', 'yes')
        ->assertExitCode(0);

    $avatar->refresh();
    expect($avatar->cdn_url)->not->toBeNull('legacy local avatar should have been migrated');
});
