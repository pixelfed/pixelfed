<?php

use App\Models\Media;
use App\Models\User;
use App\Util\Media\Image;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Image transform — supersede cleanup
|--------------------------------------------------------------------------
|
| Regenerating a thumbnail whose output extension differs from the one on
| disk used to leave the previous _thumb file orphaned in the media dir.
| The transform now deletes the file it supersedes.
|
*/

beforeEach(function () {
    // Use a faked cloud disk so Image's transform goes through the Storage
    // facade end-to-end (the local branch reads/writes via raw storage_path(),
    // which Storage::fake does not intercept).
    Config::set('filesystems.default', 's3');
    Config::set('pixelfed.optimize_image', false);
    Storage::fake('s3', ['url' => 'https://cdn.test']);
});

function seedPngMediaWithStaleThumb(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;

    $leaf = 'public/m/_v2/'.$pid.'/aa/bb';
    $mediaPath = $leaf.'/photo.png';
    // A pre-existing thumbnail with a DIFFERENT extension than the png source
    // will produce on regeneration (png source -> photo_thumb.png).
    $staleThumb = $leaf.'/photo_thumb.jpeg';

    // A real, decodable 4x4 PNG so the GD driver can process it.
    $im = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($im);
    $pngBytes = ob_get_clean();
    imagedestroy($im);

    Storage::disk('s3')->put($mediaPath, $pngBytes);
    Storage::disk('s3')->put($staleThumb, 'OLD-THUMB-BYTES');

    return Media::create([
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $mediaPath,
        'thumbnail_path' => $staleThumb,
        'mime' => 'image/png',
        'size' => strlen($pngBytes),
        'remote_media' => false,
        'order' => 0,
    ]);
}

it('deletes the superseded thumbnail when regeneration changes its extension', function () {
    $media = seedPngMediaWithStaleThumb();
    $staleThumb = $media->thumbnail_path;
    $disk = Storage::disk('s3');

    expect($disk->exists($staleThumb))->toBeTrue();

    (new Image)->resizeThumbnail($media);
    $media->refresh();

    // thumbnail_path now points at the freshly generated file (png output).
    expect($media->thumbnail_path)->not->toBe($staleThumb);
    expect($disk->exists($media->thumbnail_path))->toBeTrue();

    // The old, superseded thumbnail is gone rather than orphaned in the dir.
    expect($disk->exists($staleThumb))->toBeFalse();
});

it('keeps the thumbnail when regeneration writes to the same path', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $leaf = 'public/m/_v2/'.$pid.'/cc/dd';
    $mediaPath = $leaf.'/photo.png';

    $im = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($im);
    $pngBytes = ob_get_clean();
    imagedestroy($im);
    Storage::disk('s3')->put($mediaPath, $pngBytes);

    $media = Media::create([
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $mediaPath,
        'mime' => 'image/png',
        'size' => strlen($pngBytes),
        'remote_media' => false,
        'order' => 0,
    ]);

    (new Image)->resizeThumbnail($media);
    $media->refresh();

    // photo_thumb.png is generated and present.
    expect($media->thumbnail_path)->toBe($leaf.'/photo_thumb.png');
    expect(Storage::disk('s3')->exists($media->thumbnail_path))->toBeTrue();
});
