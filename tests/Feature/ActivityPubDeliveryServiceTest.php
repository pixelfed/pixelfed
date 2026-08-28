<?php

use App\Models\User;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

describe('ActivityPubDeliveryService::pool()', function () {
    it('delivers activity to all audience inboxes via POST', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $audience = [
            'https://remote1.example/inbox',
            'https://remote2.example/inbox',
            'https://remote3.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#delete'),
            'type' => 'Delete',
            'actor' => $profile->permalink(),
            'object' => [
                'type' => 'Person',
                'id' => $profile->permalink(),
            ],
        ];

        ActivityPubDeliveryService::pool($profile, $audience, $activity);

        Http::assertSentCount(3);

        Http::assertSent(fn ($request) => $request->url() === 'https://remote1.example/inbox'
            && $request->method() === 'POST'
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://remote2.example/inbox'
            && $request->method() === 'POST'
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://remote3.example/inbox'
            && $request->method() === 'POST'
        );
    });

    it('includes correct content type and signature headers', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $audience = ['https://remote.example/inbox'];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#test'),
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        ActivityPubDeliveryService::pool($profile, $audience, $activity);

        Http::assertSent(function ($request) {
            $contentType = $request->header('Content-Type')[0] ?? '';
            $hasSignature = ! empty($request->header('Signature')[0] ?? '');

            return str_contains($contentType, 'application/ld+json')
                && $hasSignature;
        });
    });

    it('sends JSON-encoded activity as the request body', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $audience = ['https://remote.example/inbox'];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#test'),
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        ActivityPubDeliveryService::pool($profile, $audience, $activity);

        Http::assertSent(function ($request) use ($activity) {
            $body = json_decode($request->body(), true);

            return $body === $activity;
        });
    });

    it('does nothing when audience is empty', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Delete',
        ];

        ActivityPubDeliveryService::pool($profile, [], $activity);

        Http::assertNothingSent();
    });

    it('invokes onError callback for failed responses', function () {
        Http::fake([
            'https://good.example/inbox' => Http::response('', 202),
            'https://bad.example/inbox' => Http::response('', 500),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $audience = [
            'https://good.example/inbox',
            'https://bad.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Delete',
            'actor' => $profile->permalink(),
        ];

        $errors = [];
        ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) use (&$errors) {
            $errors[] = $index;
        });

        expect($errors)->toHaveCount(1);
    });

    it('does not invoke onError callback when all requests succeed', function () {
        Http::fake([
            'https://remote1.example/inbox' => Http::response('', 202),
            'https://remote2.example/inbox' => Http::response('', 200),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $audience = [
            'https://remote1.example/inbox',
            'https://remote2.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        $errors = [];
        ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) use (&$errors) {
            $errors[] = $index;
        });

        expect($errors)->toBeEmpty();
    });
});
