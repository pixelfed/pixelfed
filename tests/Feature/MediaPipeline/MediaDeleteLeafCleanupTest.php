<?php

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MediaDeletePipeline — in-flow leaf directory cleanup
|--------------------------------------------------------------------------
|
| A media delete removes its own files AND the leaf directory it emptied
| (public/m/_v2/{pid}/{month}/{random}), so the flow does not leave empty
| folders behind for the scheduled sweep to find.
|
*/

beforeEach(function () {
    Config::set('filesystems.local', 'local');
    Config::set('pixelfed.cloud_storage', false);
    Storage::fake('local');
});

function makeOrphanLocalMedia(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;

    $leaf = 'public/m/_v2/'.$pid.'/aa-bb/rndrndrndrnd';
    $path = $leaf.'/file.jpg';
    $thumb = $leaf.'/file_thumb.jpeg';

    Storage::disk('local')->put($path, 'PRIMARY');
    Storage::disk('local')->put($thumb, 'THUMB');

    return Media::create([
        'status_id' => null,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'thumbnail_path' => $thumb,
        'mime' => 'image/jpeg',
        'size' => 7,
        'remote_media' => false,
        'order' => 0,
    ]);
}

it('deletes the media files and removes its now-empty leaf directory', function () {
    $media = makeOrphanLocalMedia();
    $leaf = implode('/', array_slice(explode('/', $media->media_path), 0, -1));
    $disk = Storage::disk('local');

    expect($disk->exists($media->media_path))->toBeTrue();

    (new MediaDeletePipeline($media))->handle();

    expect($disk->exists($media->media_path))->toBeFalse();
    expect($disk->exists($media->thumbnail_path))->toBeFalse();
    expect($disk->directoryExists($leaf))->toBeFalse();
});

it('leaves the leaf directory in place when another file still lives there', function () {
    $media = makeOrphanLocalMedia();
    $leaf = implode('/', array_slice(explode('/', $media->media_path), 0, -1));
    $disk = Storage::disk('local');

    // A sibling file that this media does not own.
    $disk->put($leaf.'/sibling.jpg', 'KEEP');

    (new MediaDeletePipeline($media))->handle();

    expect($disk->exists($media->media_path))->toBeFalse();
    expect($disk->directoryExists($leaf))->toBeTrue();
    expect($disk->exists($leaf.'/sibling.jpg'))->toBeTrue();
});
