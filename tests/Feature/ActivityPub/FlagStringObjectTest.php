<?php

use App\Models\Profile;
use App\Models\RemoteReport;
use App\Models\User;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Inbound Flag with a single-URI string object
|--------------------------------------------------------------------------
|
| ActivityStreams 2.0 lets a Flag carry its object as a single URI string.
| The inbox handler only iterated an array, so a string object warned and
| ran zero loop iterations, persisting no RemoteReport. A string object is
| now normalised to a one-element array before the loop.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'federation.activitypub.enabled' => true,
    ]);
});

function flagStringLocalTarget(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function flagStringDeliver(string $uri, string|array $object): void
{
    $payload = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $uri,
        'type' => 'Flag',
        'actor' => 'https://remote.example/users/mod',
        'object' => $object,
        'content' => 'spam report',
    ];

    (new Inbox([], null, $payload))->handle();
}

it('creates a RemoteReport when the Flag object is a single URI string', function () {
    $target = flagStringLocalTarget();
    $objectUrl = $target->permalink();
    $uri = 'https://remote.example/reports/1';

    flagStringDeliver($uri, $objectUrl);

    $report = RemoteReport::where('uri', $uri)->where('account_id', $target->id)->first();

    expect($report)->not->toBeNull();
    expect($report->report_meta['object'])->toBe([$objectUrl]);
});

it('still creates a RemoteReport when the Flag object is an array of URIs', function () {
    $target = flagStringLocalTarget();
    $objectUrl = $target->permalink();
    $uri = 'https://remote.example/reports/2';

    flagStringDeliver($uri, [$objectUrl]);

    $report = RemoteReport::where('uri', $uri)->where('account_id', $target->id)->first();

    expect($report)->not->toBeNull();
    expect($report->report_meta['object'])->toBe([$objectUrl]);
});

it('raises no warning or exception for a string Flag object', function () {
    $target = flagStringLocalTarget();
    $objectUrl = $target->permalink();
    $uri = 'https://remote.example/reports/3';

    $foreachWarning = null;

    set_error_handler(function ($severity, $message) use (&$foreachWarning) {
        if (str_contains($message, 'foreach()')) {
            $foreachWarning = $message;
        }

        return false;
    });

    try {
        flagStringDeliver($uri, $objectUrl);
    } finally {
        restore_error_handler();
    }

    expect($foreachWarning)->toBeNull();
    expect(RemoteReport::where('uri', $uri)->exists())->toBeTrue();
});
