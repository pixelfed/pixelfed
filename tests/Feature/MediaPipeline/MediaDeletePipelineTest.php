<?php

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MediaDeletePipeline skip logging
|--------------------------------------------------------------------------
|
| When media is still attached to a status, the delete job must skip deletion
| and log rich context (media/status/profile ids, paths, etc.) so operators
| can trace why an orphan purge was skipped instead of a bare message.
|
*/

it('skips deletion and logs metadata when media is still attached to a status', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo']);

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/abc.jpeg',
        'mime' => 'image/jpeg',
        'size' => 12345,
        'order' => 1,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context = []) use ($media, $status, $user) {
            return $message === 'MediaDeletePipeline: Media is attached to a status, skipping deletion'
                && $context['media_id'] === $media->id
                && $context['status_id'] === $status->id
                && $context['profile_id'] === $user->profile->id
                && $context['user_id'] === $user->id
                && $context['mime'] === 'image/jpeg'
                && $context['media_path'] === 'public/m/_v2/1/abc.jpeg';
        });

    (new MediaDeletePipeline($media))->handle();

    // Media must not be deleted while still attached.
    expect(Media::whereId($media->id)->exists())->toBeTrue();
});
