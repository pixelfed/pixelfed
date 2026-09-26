<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Status::mediaUrl() must not crash when the status has no media
|--------------------------------------------------------------------------
|
| mediaUrl() dereferenced firstMedia()->media_path with no null check, so a
| status with no attached media threw "attempt to read property on null".
| It now returns null when there is no media.
|
*/

it('returns null when the status has no media', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'text',
    ]);

    expect($status->mediaUrl())->toBeNull();
});

it('returns a url when the status has media', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    Media::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile_id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/photo.jpg',
        'mime' => 'image/jpeg',
        'order' => 1,
    ]);

    expect($status->mediaUrl())->toBeString();
    expect($status->mediaUrl())->toContain('photo.jpg');
});
