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
| StatusRemoteUpdatePipeline SSRF hardening
|--------------------------------------------------------------------------
|
| updateMedia() re-fetches remote attachments by issuing a server-side HEAD to
| the attacker-controlled attachment[*].url. The URL must be validated and
| fetched through the SSRF-hardened path (SecureMediaFetchService), so it can
| never target internal / loopback / link-local / non-https addresses.
|
*/

function remoteNoteStatus(): Status
{
    $user = User::factory()->create();
    $user->refresh();

    $objectUrl = 'https://remote.example/users/bob/statuses/1';

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'local' => false,
        'uri' => $objectUrl,
        'object_url' => $objectUrl,
    ]);

    return $status;
}

function updateActivityWithAttachmentUrl(string $objectUrl, string $attachmentUrl): array
{
    return [
        'id' => $objectUrl,
        'type' => 'Update',
        'content' => 'updated caption',
        'attachment' => [
            [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $attachmentUrl,
            ],
        ],
    ];
}

it('does not issue a server-side request to a link-local metadata address', function () {
    Http::fake();

    $status = remoteNoteStatus();
    $activity = updateActivityWithAttachmentUrl(
        $status->object_url,
        'http://169.254.169.254/latest/meta-data/'
    );

    (new StatusRemoteUpdatePipeline($activity))->handle();

    Http::assertNothingSent();
    expect(Media::whereStatusId($status->id)->whereRemoteUrl('http://169.254.169.254/latest/meta-data/')->exists())->toBeFalse();
});

it('does not issue a server-side request to a loopback address', function () {
    Http::fake();

    $status = remoteNoteStatus();
    $activity = updateActivityWithAttachmentUrl(
        $status->object_url,
        'http://127.0.0.1:9000/internal'
    );

    (new StatusRemoteUpdatePipeline($activity))->handle();

    Http::assertNothingSent();
});

it('rejects an https URL whose host is a private IP literal', function () {
    Http::fake();

    $status = remoteNoteStatus();
    $activity = updateActivityWithAttachmentUrl(
        $status->object_url,
        'https://10.0.0.1/admin.jpg'
    );

    (new StatusRemoteUpdatePipeline($activity))->handle();

    Http::assertNothingSent();
});

it('persists media for a valid https attachment via the hardened HEAD path', function () {
    // Pre-seed the DNS resolution cache so the hardened fetch treats the host as
    // publicly resolvable without a real network lookup, keeping the test
    // deterministic.
    Cache::put(
        'helpers:url:public-ips:'.hash('xxh128', 'media.example'),
        ['203.0.113.20'],
        3600
    );

    Http::fake([
        'https://media.example/photo.jpg' => Http::response('', 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => '50000',
        ]),
    ]);

    $status = remoteNoteStatus();
    $activity = updateActivityWithAttachmentUrl(
        $status->object_url,
        'https://media.example/photo.jpg'
    );

    (new StatusRemoteUpdatePipeline($activity))->handle();

    $media = Media::whereStatusId($status->id)->whereRemoteUrl('https://media.example/photo.jpg')->first();

    expect($media)->not->toBeNull()
        ->and($media->mime)->toBe('image/jpeg');
});
