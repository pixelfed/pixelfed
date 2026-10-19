<?php

use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Cross-domain handle verification
|--------------------------------------------------------------------------
|
| A remote actor can point its own webfinger at an acct: subject on another
| domain (an alias). Before trusting that claim we fetch the claimed domain's
| webfinger and only accept it when its self link resolves back to the exact
| same actor id. Anything else, and any local/banned domain or malformed
| handle, falls back to the actor's own host.
|
*/

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * real network lookup. Mirrors the fcSeedHosts helper in InboxFirstContactTest.
 */
function vhSeedHosts(array $hosts, array $banned = []): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => Helpers::URL_OK, 'ips' => ['203.0.113.40']],
            3600
        );
    }

    Cache::put('instances:banned:domains', $banned, 1209600);
}

/**
 * A webfinger record for the actor's own host that claims an acct: subject.
 */
function vhSubjectResponse(string $subject)
{
    return Http::response(['subject' => $subject], 200, [
        'Content-Type' => 'application/jrd+json',
    ]);
}

/**
 * A webfinger record for the claimed domain whose self link is $href.
 */
function vhSelfLinkResponse(string $href)
{
    return Http::response([
        'subject' => 'acct:someone@example',
        'links' => [
            ['rel' => 'http://webfinger.net/rel/profile-page', 'type' => 'text/html', 'href' => $href],
            ['rel' => 'self', 'type' => 'application/activity+json', 'href' => $href],
        ],
    ], 200, ['Content-Type' => 'application/jrd+json']);
}

describe('verified handle', function () {
    it('accepts a cross-domain handle that resolves back to the same actor id', function () {
        $actorId = 'https://alias.example/users/alice';
        vhSeedHosts(['alias.example', 'canonical.example']);

        Http::fake([
            'alias.example/.well-known/webfinger*' => vhSubjectResponse('acct:alice@canonical.example'),
            'canonical.example/.well-known/webfinger*' => vhSelfLinkResponse($actorId),
        ]);

        expect(Helpers::verifiedHandle('alice', 'alias.example', $actorId))
            ->toBe(['alice', 'canonical.example']);
    });

    it('falls back when the claimed domain points at a different actor (spoof)', function () {
        $actorId = 'https://spoof.example/users/alice';
        vhSeedHosts(['spoof.example', 'canonical.example']);

        Http::fake([
            'spoof.example/.well-known/webfinger*' => vhSubjectResponse('acct:alice@canonical.example'),
            // canonical.example disavows the claim, its self link is someone else
            'canonical.example/.well-known/webfinger*' => vhSelfLinkResponse('https://canonical.example/users/someoneelse'),
        ]);

        expect(Helpers::verifiedHandle('alice', 'spoof.example', $actorId))
            ->toBe(['alice', 'spoof.example']);
    });

    it('falls back for a claim on the local app domain without consulting the claimed webfinger', function () {
        $appDomain = config('pixelfed.domain.app');
        $actorId = 'https://local-claim.example/users/alice';
        vhSeedHosts(['local-claim.example']);

        Http::fake([
            'local-claim.example/.well-known/webfinger*' => vhSubjectResponse("acct:alice@{$appDomain}"),
            '*' => Http::response('unexpected', 500),
        ]);

        expect(Helpers::verifiedHandle('alice', 'local-claim.example', $actorId))
            ->toBe(['alice', 'local-claim.example']);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), $appDomain.'/.well-known/webfinger'));
    });

    it('falls back for a claim on a banned domain', function () {
        $actorId = 'https://banned-claim.example/users/alice';
        vhSeedHosts(['banned-claim.example', 'banned.example'], ['banned.example']);

        Http::fake([
            'banned-claim.example/.well-known/webfinger*' => vhSubjectResponse('acct:alice@banned.example'),
            'banned.example/.well-known/webfinger*' => vhSelfLinkResponse($actorId),
        ]);

        expect(Helpers::verifiedHandle('alice', 'banned-claim.example', $actorId))
            ->toBe(['alice', 'banned-claim.example']);
    });

    it('falls back for a handle with an illegal character', function () {
        $actorId = 'https://badchar.example/users/alice';
        vhSeedHosts(['badchar.example', 'canonical.example']);

        Http::fake([
            'badchar.example/.well-known/webfinger*' => vhSubjectResponse('acct:a*b@canonical.example'),
            'canonical.example/.well-known/webfinger*' => vhSelfLinkResponse($actorId),
        ]);

        expect(Helpers::verifiedHandle('alice', 'badchar.example', $actorId))
            ->toBe(['alice', 'badchar.example']);
    });

    it('falls back when the actor webfinger has no acct subject', function () {
        $actorId = 'https://nosubject.example/users/alice';
        vhSeedHosts(['nosubject.example']);

        Http::fake([
            'nosubject.example/.well-known/webfinger*' => Http::response([
                'links' => [['rel' => 'self', 'type' => 'application/activity+json', 'href' => $actorId]],
            ], 200, ['Content-Type' => 'application/jrd+json']),
        ]);

        expect(Helpers::verifiedHandle('alice', 'nosubject.example', $actorId))
            ->toBe(['alice', 'nosubject.example']);
    });
});
