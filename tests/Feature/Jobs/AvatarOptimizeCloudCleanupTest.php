<?php

use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Models\Avatar;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| AvatarOptimize cloud cleanup - regression
|--------------------------------------------------------------------------
|
| When both cloud_storage and avatar.local_to_cloud are enabled, the job
| mirrors the optimized avatar to the cloud disk and must delete the local
| copy. uploadToCloud() reassigned media_path to the cloud key before the
| Storage::delete() call, so the delete ran against the wrong path on the
| local disk and silently leaked the local file.
|
| The local disk and storage_path() both point at the real storage/app, so
| we only fake the cloud disk. A real avatar file is written to a unique
| splayed path and cleaned up afterwards.
|
*/

it('deletes the local avatar file after uploading it to the cloud', function () {
    config(['pixelfed.cloud_storage' => true]);
    config(['instance.avatar.local_to_cloud' => true]);
    config(['filesystems.cloud' => 's3']);

    Storage::fake('s3');

    $user = User::factory()->create();
    $user->refresh();

    // Unique local avatar file so the test never collides with real storage.
    $mediaPath = 'public/avatars/test/'.$user->profile_id.'/avatar_'.uniqid().'.jpg';
    $absolute = storage_path('app/'.$mediaPath);
    @mkdir(dirname($absolute), 0755, true);
    $img = imagecreatetruecolor(300, 300);
    imagefilledrectangle($img, 0, 0, 299, 299, imagecolorallocate($img, 120, 90, 200));
    imagejpeg($img, $absolute);
    imagedestroy($img);

    expect(is_file($absolute))->toBeTrue();

    Avatar::updateOrCreate(
        ['profile_id' => $user->profile_id],
        ['media_path' => $mediaPath, 'change_count' => 0]
    );

    try {
        (new AvatarOptimize($user->profile, false))->handle();

        $avatar = Avatar::whereProfileId($user->profile_id)->firstOrFail();

        // Local file must be gone; media_path now points at the cloud key.
        expect(is_file($absolute))->toBeFalse('the local optimized avatar was leaked');
        expect($avatar->media_path)->toStartWith('cache/avatars/'.$user->profile_id);
        expect($avatar->cdn_url)->not->toBeNull();
        Storage::disk('s3')->assertExists($avatar->media_path);
    } finally {
        @unlink($absolute);
        @rmdir(dirname($absolute));
    }
});
