<?php

use App\Models\DmConversationParticipant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

require_once __DIR__.'/helpers.php';

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    $this->withoutMiddleware(ThrottleRequests::class);

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
        'dm.groups.enabled' => true,
        'snowflake.datacenter_id' => 1,
        'snowflake.worker_id' => 1,
    ]);
});

describe('re-opening a hidden conversation', function () {
    it('un-hides a one to one conversation when the caller opens it again', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hi'])->assertCreated();

        $this->postJson("/api/v1.1/direct/conversations/{$id}/hide")->assertOk()->assertJsonPath('hidden', true);

        $this->getJson('/api/v1.1/direct/conversations?filter=primary')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1.1/direct/conversations?filter=hidden')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/conversations')->assertJsonCount(0);

        $reopen = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]]);
        $reopen->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('hidden', false);

        $this->getJson('/api/v1.1/direct/conversations?filter=primary')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);
        $this->getJson('/api/v1.1/direct/conversations?filter=hidden')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/conversations')->assertJsonCount(1);

        expect(DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $alice->profile_id)->value('hidden_at'))->toBeNull();
    });

    it('re-activates and un-hides a group member who hid it then left', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $carol = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id, $carol->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hi all'])->assertCreated();

        Passport::actingAs($carol, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/hide")->assertOk()->assertJsonPath('hidden', true);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/leave")->assertOk()->assertJsonPath('state', 'left');

        $rejoin = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$alice->profile_id, $bob->profile_id]]);
        $rejoin->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('state', 'active')
            ->assertJsonPath('hidden', false);

        $participant = DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $carol->profile_id)->first();
        expect($participant->state)->toBe(DmConversationParticipant::STATE_ACTIVE)
            ->and($participant->hidden_at)->toBeNull();

        $this->getJson('/api/v1.1/direct/conversations?filter=primary')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);
    });

    it('opens a fresh conversation normally without touching other participants', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $response = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]]);
        $response->assertCreated()->assertJsonPath('hidden', false);

        $id = $response->json('id');

        expect(DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $alice->profile_id)->value('hidden_at'))->toBeNull()
            ->and(DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $bob->profile_id)->value('hidden_at'))->toBeNull();
    });

    it('does not change other participants hidden state when the caller re-opens', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hi'])->assertCreated();

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/hide")->assertOk()->assertJsonPath('hidden', true);

        Passport::actingAs($alice, ['read', 'write']);
        $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->assertOk();

        expect(DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $bob->profile_id)->value('hidden_at'))->not->toBeNull()
            ->and(DmConversationParticipant::where('conversation_id', $id)->where('profile_id', $alice->profile_id)->value('hidden_at'))->toBeNull();
    });
});
