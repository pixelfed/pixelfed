<?php

use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Report;
use App\Services\DirectMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

require_once __DIR__.'/helpers.php';

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Legacy thread endpoints and the Mastodon conversations API
|--------------------------------------------------------------------------
|
| The Blade UI and the older mobile app address a thread by the other
| person's profile id. Those endpoints keep their request and response
| shapes and now read and write conversations.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    // Ids minted in the same millisecond only sort by creation order when the
    // worker bits are fixed. Left unset they are random for every id.
    config(['snowflake.datacenter_id' => 1, 'snowflake.worker_id' => 1]);

    $this->withoutMiddleware(ThrottleRequests::class);

    // Ids minted in the same millisecond only sort by creation order when the
    // worker bits are fixed. Left unset they are random for every id.
    config(['snowflake.datacenter_id' => 1, 'snowflake.worker_id' => 1]);

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

describe('thread endpoints', function () {
    it('sends through thread/send and reads it back through thread', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $sent = $this->postJson('/api/v1.1/direct/thread/send', [
            'to_id' => $bob->profile_id,
            'message' => 'hello bob',
            'type' => 'text',
        ]);

        $sent->assertOk()
            ->assertJsonPath('isAuthor', true)
            ->assertJsonPath('type', 'text')
            ->assertJsonPath('text', 'hello bob');

        expect($sent->json('reportId'))->toBe($sent->json('id'))
            ->and(DmConversation::count())->toBe(1);

        Passport::actingAs($bob, ['read', 'write']);

        $thread = $this->getJson('/api/v1.1/direct/thread?pid='.$alice->profile_id);

        $thread->assertOk()
            ->assertJsonPath('id', (string) $alice->profile_id)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.text', 'hello bob')
            ->assertJsonPath('messages.0.isAuthor', false)
            ->assertJsonPath('messages.0.seen', false);

        expect($thread->json('conversation_id'))->toBe((string) DmConversation::first()->id);
    });

    it('returns an empty thread for someone never messaged', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $this->getJson('/api/v1.1/direct/thread?pid='.$bob->profile_id)
            ->assertOk()
            ->assertJsonCount(0, 'messages')
            ->assertJsonPath('conversation_id', null);
    });

    it('uploads media with text as a single message', function () {
        Storage::fake(config('filesystems.default'));

        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $this->post('/api/v1.1/direct/thread/media', [
            'to_id' => $bob->profile_id,
            'file' => UploadedFile::fake()->image('cat.jpg', 100, 100),
            'message' => 'my cat',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('type', 'photo');

        $message = DmMessage::with('media')->first();

        expect(DmMessage::count())->toBe(1)
            ->and($message->body)->toBe('my cat')
            ->and($message->media)->toHaveCount(1)
            ->and($message->media[0]->status_id)->toBeNull();

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson('/api/v1.1/direct/thread?pid='.$alice->profile_id)
            ->assertJsonPath('messages.0.text', 'my cat')
            ->assertJsonPath('messages.0.type', 'photo')
            ->assertJsonCount(1, 'messages.0.carousel');

        expect($this->getJson('/api/v1.1/direct/thread?pid='.$alice->profile_id)->json('messages.0.media'))->not->toBeNull();
    });

    it('marks a thread read and reports it as seen to the sender', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/thread/send', ['to_id' => $bob->profile_id, 'message' => 'one', 'type' => 'text'])->json('id');

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson('/api/v1.1/direct/thread/read', ['pid' => $alice->profile_id, 'sid' => $id])
            ->assertOk()
            ->assertExactJson([$id]);

        expect(DmConversationParticipant::where('profile_id', $bob->profile_id)->value('unread_count'))->toBe(0);

        Passport::actingAs($alice, ['read', 'write']);
        $this->getJson('/api/v1.1/direct/thread?pid='.$bob->profile_id)->assertJsonPath('messages.0.seen', true);
    });

    it('deletes by the id the thread handed out', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/thread/send', ['to_id' => $bob->profile_id, 'message' => 'oops', 'type' => 'text'])->json('reportId');

        Passport::actingAs($bob, ['read', 'write']);
        $this->deleteJson('/api/v1.1/direct/thread/message', ['id' => $id])->assertNotFound();

        Passport::actingAs($alice, ['read', 'write']);
        $this->deleteJson('/api/v1.1/direct/thread/message', ['id' => $id])->assertOk();

        expect(DmMessage::count())->toBe(0);
    });

    it('mutes and unmutes a thread', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson('/api/v1.1/direct/thread/mute', ['id' => $bob->profile_id])->assertOk();
        expect(DmConversationParticipant::where('profile_id', $alice->profile_id)->value('muted_at'))->not->toBeNull();

        $this->postJson('/api/v1.1/direct/thread/unmute', ['id' => $bob->profile_id])->assertOk();
        expect(DmConversationParticipant::where('profile_id', $alice->profile_id)->value('muted_at'))->toBeNull();
    });

    it('lets an older client report a message as a post', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/thread/send', ['to_id' => $bob->profile_id, 'message' => 'spam', 'type' => 'text'])->json('reportId');

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson('/api/v1.1/report', ['report_type' => 'spam', 'object_type' => 'post', 'object_id' => $id])->assertOk();

        expect(Report::first()->object_type)->toBe(DmMessage::class);
    });
});

describe('GET /api/v1/conversations', function () {
    it('lists one to one conversations with a status shaped last message', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $message = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'hello']);

        Passport::actingAs($bob, ['read', 'write']);

        $response = $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.unread', true)
            ->assertJsonPath('0.accounts.0.id', (string) $alice->profile_id)
            ->assertJsonPath('0.last_status.id', (string) $message->id)
            ->assertJsonPath('0.last_status.visibility', 'direct')
            ->assertJsonPath('0.last_status.content', '<p>hello</p>')
            ->assertJsonPath('0.last_status.account.id', (string) $alice->profile_id)
            ->assertJsonPath('0.last_status.mentions.0.id', (string) $bob->profile_id);

        // Pixelix stores this id as a 32-bit int, as the old endpoint allowed
        $id = $response->json('0.id');
        expect((int) $id)->toBeLessThan(2 ** 31)
            ->and((int) $id)->toBeGreaterThan(0)
            ->and((int) $id)->toBe(DmConversationParticipant::where('profile_id', $bob->profile_id)->value('id'));

        $this->postJson("/api/v1/conversations/{$id}/read")->assertOk()->assertJsonPath('unread', false);

        // The real conversation id still works for clients written against it
        $this->postJson("/api/v1/conversations/{$conversation->id}/read")->assertOk();

        $this->deleteJson("/api/v1/conversations/{$id}")->assertOk();
        $this->getJson('/api/v1/conversations')->assertJsonCount(0);
    });

    it('does not let one person use another persons conversation id', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $eve = dmLocalUser();

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $service->sendMessage($conversation, dmProfile($alice), ['body' => 'hello']);

        Passport::actingAs($bob, ['read', 'write']);
        $id = $this->getJson('/api/v1/conversations')->json('0.id');

        Passport::actingAs($eve, ['read', 'write']);
        $this->postJson("/api/v1/conversations/{$id}/read")->assertNotFound();
        $this->deleteJson("/api/v1/conversations/{$id}")->assertNotFound();
        $this->deleteJson("/api/v1/conversations/{$conversation->id}")->assertNotFound();
    });

    it('only includes groups when asked', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $dave = dmLocalUser();

        $service = app(DirectMessageService::class);
        $group = $service->findOrCreateConversation(dmProfile($alice), collect([dmProfile($bob), dmProfile($dave)]));
        $service->sendMessage($group, dmProfile($alice), ['body' => 'hi all']);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson('/api/v1/conversations')->assertJsonCount(0);
        $this->getJson('/api/v1/conversations?include_groups=1')
            ->assertJsonCount(1)
            ->assertJsonCount(2, '0.accounts');
    });

    it('keeps requests out of the inbox', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser(publicDm: false);

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $service->sendMessage($conversation, dmProfile($alice), ['body' => 'hello']);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson('/api/v1/conversations')->assertJsonCount(0);
        $this->getJson('/api/v1/conversations?scope=requests')->assertJsonCount(1);
    });
});

describe('mastodon status endpoints', function () {
    it('serves a message as a status to people in the conversation and nobody else', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $eve = dmLocalUser();

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $message = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'hello']);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson("/api/v1/statuses/{$message->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $message->id)
            ->assertJsonPath('visibility', 'direct')
            ->assertJsonPath('content', '<p>hello</p>')
            ->assertJsonPath('account.id', (string) $alice->profile_id)
            ->assertJsonPath('mentions.0.id', (string) $bob->profile_id);

        Passport::actingAs($eve, ['read', 'write']);

        $this->getJson("/api/v1/statuses/{$message->id}")->assertNotFound();
    });

    it('returns the rest of the conversation as the context of a message', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $one = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'one']);
        $two = $service->sendMessage($conversation, dmProfile($bob), ['body' => 'two']);
        $three = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'three']);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson("/api/v1/statuses/{$two->id}/context")
            ->assertOk()
            ->assertJsonCount(1, 'ancestors')
            ->assertJsonPath('ancestors.0.id', (string) $one->id)
            ->assertJsonCount(1, 'descendants')
            ->assertJsonPath('descendants.0.id', (string) $three->id)
            ->assertJsonPath('descendants.0.visibility', 'direct');

        // The usual client flow: open the conversation from its last status
        $this->getJson("/api/v1/statuses/{$three->id}/context")
            ->assertOk()
            ->assertJsonCount(2, 'ancestors')
            ->assertJsonCount(0, 'descendants');
    });

    it('lets the author delete a message through the status endpoint', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $message = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'oops']);

        Passport::actingAs($bob, ['read', 'write']);
        $this->deleteJson("/api/v1/statuses/{$message->id}")->assertNotFound();

        Passport::actingAs($alice, ['read', 'write']);
        $this->deleteJson("/api/v1/statuses/{$message->id}")
            ->assertOk()
            ->assertJsonPath('text', 'oops');

        expect(DmMessage::count())->toBe(0);
    });
});

describe('media housekeeping', function () {
    it('does not let direct message media be attached to a post', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        $media = Media::create([
            'status_id' => null,
            'profile_id' => $alice->profile_id,
            'user_id' => $alice->id,
            'media_path' => 'public/m/test.jpg',
            'mime' => 'image/jpeg',
            'size' => 1000,
        ]);

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $service->sendMessage($conversation, dmProfile($alice), ['media' => collect([$media])]);

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson('/api/v1/statuses', ['media_ids' => [$media->id], 'status' => 'now public'])->assertStatus(400);

        expect($media->fresh()->status_id)->toBeNull();
    });
});
