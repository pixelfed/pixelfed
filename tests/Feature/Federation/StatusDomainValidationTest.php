<?php

use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Status domain validation metadata
|--------------------------------------------------------------------------
|
| storeStatus() rejects remote statuses whose object `id` host and canonical
| `url` host do not match (anti-spoofing guard). The thrown exception must
| carry metadata describing the checked and expected values so operators can
| diagnose why a remote status (e.g. an Announce target) was rejected.
|
*/

it('throws with checked and expected metadata when status domains mismatch', function () {
    $user = User::factory()->create();
    $user->refresh();

    $activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => 'https://a.example.org/statuses/123',
        'type' => 'Note',
        'url' => 'https://b.example.org/statuses/123',
        'published' => now()->toAtomString(),
        'content' => 'hello',
    ];

    try {
        Helpers::storeStatus($activity['url'], $user->profile, $activity);
        $this->fail('Expected storeStatus to throw for mismatched domains');
    } catch (Exception $e) {
        $context = json_decode($e->getMessage(), true);

        expect($context)->toBeArray();
        expect($context['message'])->toBe('Invalid status domains');
        expect($context['checked']['id_host'])->toBe('a.example.org');
        expect($context['checked']['url_host'])->toBe('b.example.org');
        expect($context['expected'])->toContain('match');
        expect($context['payload'])->toBe($activity);
    }
});
