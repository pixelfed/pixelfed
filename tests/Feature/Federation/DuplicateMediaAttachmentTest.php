<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Duplicate media attachment on remote import
|--------------------------------------------------------------------------
|
| Regression test for the 1062 duplicate-key violation on
| media_status_id_media_path_unique when importing remote status attachments
| (e.g. an Announce racing another inbox job, or a re-fetch). Media import
| must be idempotent on (status_id, media_path).
|
*/

function attachmentPayload(string $url): array
{
    return [
        'type' => 'Document',
        'mediaType' => 'image/jpeg',
        'url' => $url,
        'name' => 'alt text',
        'blurhash' => 'UREVf}R:E2WB~qNKWBs.XURkxZofD+n~oJR-',
        'width' => 768,
        'height' => 1024,
    ];
}

it('does not create a duplicate media row for the same status and url', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo']);

    $url = 'https://files.mastodon.social/media_attachments/files/117/original/e9ab6f0314043b12.jpeg';
    $payload = attachmentPayload($url);

    $first = Helpers::createMediaAttachment($payload, $status, 0);
    // Second call (simulating re-import / race) must not throw and must be a no-op.
    $second = Helpers::createMediaAttachment($payload, $status, 0);

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect(Media::whereStatusId($status->id)->whereMediaPath($url)->count())->toBe(1);
});

it('creates distinct rows for different urls on the same status', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo:album']);

    $a = Helpers::createMediaAttachment(attachmentPayload('https://files.mastodon.social/a.jpeg'), $status, 0);
    $b = Helpers::createMediaAttachment(attachmentPayload('https://files.mastodon.social/b.jpeg'), $status, 1);

    expect($a)->not->toBeNull();
    expect($b)->not->toBeNull();
    expect(Media::whereStatusId($status->id)->count())->toBe(2);
});

it('returns null when the row was inserted concurrently after the existence check', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo']);

    $url = 'https://files.mastodon.social/race.jpeg';

    // Pre-insert the row to simulate the concurrent winner.
    Media::create([
        'remote_media' => true,
        'status_id' => $status->id,
        'profile_id' => $status->profile_id,
        'media_path' => $url,
        'remote_url' => $url,
        'mime' => 'image/jpeg',
        'version' => 3,
        'order' => 1,
    ]);

    // Should detect the existing row and return null without throwing.
    $result = Helpers::createMediaAttachment(attachmentPayload($url), $status, 0);

    expect($result)->toBeNull();
    expect(Media::whereStatusId($status->id)->whereMediaPath($url)->count())->toBe(1);
});
