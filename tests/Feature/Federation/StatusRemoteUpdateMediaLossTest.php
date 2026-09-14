<?php

use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusRemoteUpdatePipeline media-loss safety
|--------------------------------------------------------------------------
|
| updateMedia() re-fetches replacement attachments through SSRF-hardened gates
| (URL + HEAD + MIME). It must validate replacements BEFORE detaching existing
| media, so a transient HEAD failure / MIME mismatch on an edit does not
| silently orphan the status's existing images (leaving it with zero media).
|
*/

function seedMediaHost(string $host): void
{
    Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.20'], 3600);
}

function remoteStatusWithMedia(string $remoteUrl = 'https://media.example/original.jpg'): Status
{
    $user = User::factory()->create();
    $user->refresh();

    $objectUrl = 'https://remote.example/users/bob/statuses/'.uniqid();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'local' => false,
        'uri' => $objectUrl,
        'object_url' => $objectUrl,
    ]);

    $media = new Media;
    $media->status_id = $status->id;
    $media->profile_id = $status->profile_id;
    $media->remote_media = true;
    $media->media_path = $remoteUrl;
    $media->remote_url = $remoteUrl;
    $media->mime = 'image/jpeg';
    $media->order = 1;
    $media->save();

    return $status;
}

function updateActivity(string $objectUrl, array $attachment): array
{
    return [
        'id' => $objectUrl,
        'type' => 'Update',
        'content' => 'updated caption',
        'attachment' => $attachment,
    ];
}

it('keeps existing media when the replacement attachment HEAD fails', function () {
    seedMediaHost('media.example');

    // The replacement fetch fails (transient 500) -> no survivors.
    Http::fake([
        'https://media.example/replacement.jpg' => Http::response('', 500),
    ]);

    $status = remoteStatusWithMedia();
    expect(Media::whereStatusId($status->id)->count())->toBe(1);

    $activity = updateActivity($status->object_url, [
        [
            'type' => 'Image',
            'mediaType' => 'image/jpeg',
            'url' => 'https://media.example/replacement.jpg',
        ],
    ]);

    (new StatusRemoteUpdatePipeline($activity))->handle();

    // Original media is retained (not orphaned) since nothing could replace it.
    expect(Media::whereStatusId($status->id)->count())->toBe(1)
        ->and(Media::whereStatusId($status->id)->first()->remote_url)
        ->toBe('https://media.example/original.jpg');
});

it('keeps existing media when the replacement served MIME is disallowed', function () {
    seedMediaHost('media.example');

    // HEAD succeeds but serves a disallowed MIME -> no survivors.
    Http::fake([
        'https://media.example/replacement.jpg' => Http::response('', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => '50000',
        ]),
    ]);

    $status = remoteStatusWithMedia();

    $activity = updateActivity($status->object_url, [
        [
            'type' => 'Image',
            'mediaType' => 'image/jpeg',
            'url' => 'https://media.example/replacement.jpg',
        ],
    ]);

    (new StatusRemoteUpdatePipeline($activity))->handle();

    expect(Media::whereStatusId($status->id)->count())->toBe(1)
        ->and(Media::whereStatusId($status->id)->first()->remote_url)
        ->toBe('https://media.example/original.jpg');
});

it('swaps media when the replacement attachment validates', function () {
    seedMediaHost('media.example');

    Http::fake([
        'https://media.example/replacement.jpg' => Http::response('', 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => '50000',
        ]),
    ]);

    $status = remoteStatusWithMedia();

    $activity = updateActivity($status->object_url, [
        [
            'type' => 'Image',
            'mediaType' => 'image/jpeg',
            'url' => 'https://media.example/replacement.jpg',
        ],
    ]);

    (new StatusRemoteUpdatePipeline($activity))->handle();

    // Old media detached, new media attached.
    $attached = Media::whereStatusId($status->id)->get();
    expect($attached)->toHaveCount(1)
        ->and($attached->first()->remote_url)->toBe('https://media.example/replacement.jpg');
    expect(Media::whereStatusId($status->id)->whereRemoteUrl('https://media.example/original.jpg')->exists())
        ->toBeFalse();
});

it('removes media when the sender sends an empty attachment array', function () {
    $status = remoteStatusWithMedia();
    expect(Media::whereStatusId($status->id)->count())->toBe(1);

    // Genuine removal: explicit empty attachment array.
    $activity = updateActivity($status->object_url, []);

    (new StatusRemoteUpdatePipeline($activity))->handle();

    // Media is intentionally cleared.
    expect(Media::whereStatusId($status->id)->count())->toBe(0);
});
