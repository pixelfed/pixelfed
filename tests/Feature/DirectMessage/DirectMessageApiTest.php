<?php

use App\Federation\ActivityBuilders\DirectMessageActivityBuilder;
use App\Jobs\Federation\DeliverDirectMessageActivity;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\DirectMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
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
        'snowflake.datacenter_id' => 1,
        'snowflake.worker_id' => 1,
    ]);
});

function dmUpload(User $user, string $mime = 'image/jpeg'): Media
{
    return Media::create([
        'status_id' => null,
        'profile_id' => $user->profile_id,
        'user_id' => $user->id,
        'media_path' => 'public/m/'.Str::random(12).'.jpg',
        'mime' => $mime,
        'size' => 1000,
    ]);
}

describe('starting a conversation', function () {
    it('creates a one to one conversation and returns the same one next time', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $first = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]]);
        $first->assertCreated()
            ->assertJsonPath('type', 'dm')
            ->assertJsonPath('participants.0.id', (string) $bob->profile_id)
            ->assertJsonPath('last_message', null);

        $second = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]]);
        $second->assertOk()->assertJsonPath('id', $first->json('id'));

        expect(DmConversation::count())->toBe(1);
    });

    it('creates a group for several recipients', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $carol = dmRemoteProfile('carol');

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id, $carol->id]])
            ->assertCreated()
            ->assertJsonPath('type', 'group')
            ->assertJsonPath('participant_count', 3)
            ->assertJsonCount(2, 'participants');
    });

    it('refuses a recipient who blocks the sender', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        UserFilter::create([
            'user_id' => $bob->profile_id,
            'filterable_id' => $alice->profile_id,
            'filterable_type' => 'App\Models\Profile',
            'filter_type' => 'block',
        ]);

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->assertForbidden();
    });

    it('makes new accounts wait', function () {
        config(['instance.allow_new_account_dms' => false]);

        $alice = dmLocalUser(attributes: ['created_at' => now()]);
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->assertStatus(400);
    });

    it('requires the write scope', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read']);

        $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->assertForbidden();
    });
});

describe('sending', function () {
    it('sends text and media as one message', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $media = dmUpload($alice);

        Passport::actingAs($alice, ['read', 'write']);

        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');

        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", [
            'message' => 'look at this',
            'media_ids' => [$media->id],
        ])
            ->assertCreated()
            ->assertJsonPath('text', 'look at this')
            ->assertJsonPath('type', 'photo')
            ->assertJsonPath('media.0.id', (string) $media->id)
            ->assertJsonPath('is_author', true);

        expect(DmMessage::count())->toBe(1);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.text', 'look at this')
            ->assertJsonPath('data.0.media.0.id', (string) $media->id)
            ->assertJsonPath('data.0.is_author', false);
    });

    it('does not accept media that belongs to someone else or is already used', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $theirs = dmUpload($bob);
        $mine = dmUpload($alice);

        Passport::actingAs($alice, ['read', 'write']);

        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');

        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['media_ids' => [$theirs->id]])->assertStatus(422);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['media_ids' => [$mine->id]])->assertCreated();
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['media_ids' => [$mine->id]])->assertStatus(422);
    });

    it('updates the recipients inbox, unread count and notifications', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);

        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hi'])->assertCreated();

        expect(Notification::where('profile_id', $bob->profile_id)->where('action', 'dm')->count())->toBe(1);

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson('/api/v1.1/direct/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.unread_count', 1)
            ->assertJsonPath('data.0.last_message.text', 'hi')
            ->assertJsonPath('data.0.participants.0.id', (string) $alice->profile_id);

        $this->getJson('/api/v1.1/direct/unread_count')->assertJsonPath('primary', 1);

        $this->postJson("/api/v1.1/direct/conversations/{$id}/read")->assertOk()->assertJsonPath('unread_count', 0);
    });

    it('holds a first message as a request and limits the sender until it is accepted', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser(publicDm: false);

        Passport::actingAs($alice, ['read', 'write']);

        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'one'])->assertCreated();
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'two'])->assertForbidden();

        Passport::actingAs($bob, ['read', 'write']);

        $this->getJson('/api/v1.1/direct/conversations')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1.1/direct/conversations?filter=requests')->assertJsonCount(1, 'data');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/accept")->assertJsonPath('state', 'active');
        $this->getJson('/api/v1.1/direct/conversations')->assertJsonCount(1, 'data');

        Passport::actingAs($alice, ['read', 'write']);

        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'two'])->assertCreated();
    });

    it('accepts a request when the recipient replies', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser(publicDm: false);

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'one'])->assertCreated();

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hey'])->assertCreated();

        expect(DmConversationParticipant::where('profile_id', $bob->profile_id)->value('state'))->toBe('active');
    });

    it('hides a conversation from people who are not in it', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $eve = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'secret'])->assertCreated();

        Passport::actingAs($eve, ['read', 'write']);

        $this->getJson("/api/v1.1/direct/conversations/{$id}")->assertNotFound();
        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages")->assertNotFound();
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hello?'])->assertNotFound();
    });

    it('pages back with max_id and polls forward with min_id', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');

        $ids = [];
        foreach (['a', 'b', 'c'] as $text) {
            $ids[] = $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => $text])->json('id');
        }

        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages?limit=2")
            ->assertJsonPath('data.0.text', 'c')
            ->assertJsonPath('data.1.text', 'b')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('meta.oldest_id', $ids[1]);

        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages?max_id={$ids[1]}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.text', 'a');

        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages?min_id={$ids[0]}")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.text', 'c');
    });
});

describe('groups', function () {
    it('delivers to every member and keeps a blocked sender out of view', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $dave = dmLocalUser();

        UserFilter::create([
            'user_id' => $dave->profile_id,
            'filterable_id' => $alice->profile_id,
            'filterable_type' => 'App\Models\Profile',
            'filter_type' => 'block',
        ]);

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id, $dave->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'from alice'])->assertCreated();

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'from bob'])->assertCreated();

        Passport::actingAs($dave, ['read', 'write']);

        $this->getJson("/api/v1.1/direct/conversations/{$id}/messages")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.text', 'from bob');

        $this->getJson('/api/v1.1/direct/conversations')->assertJsonPath('data.0.unread_count', 1);
    });

    it('lets a member leave without changing the conversation for the others', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $dave = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id, $dave->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'hi all'])->assertCreated();

        Passport::actingAs($dave, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/leave")->assertJsonPath('state', 'left');
        $this->getJson("/api/v1.1/direct/conversations/{$id}")->assertNotFound();

        Passport::actingAs($alice, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'still here'])->assertCreated();

        expect(DmConversationParticipant::where('profile_id', $dave->profile_id)->value('unread_count'))->toBe(0)
            ->and(DmConversationParticipant::where('conversation_id', $id)->count())->toBe(3);
    });

    it('cannot leave a one to one conversation', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');

        $this->postJson("/api/v1.1/direct/conversations/{$id}/leave")->assertStatus(422);
    });
});

describe('deleting and reporting', function () {
    it('lets the author delete a message and repairs the conversation', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $first = $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'one'])->json('id');
        $second = $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'two'])->json('id');

        $this->deleteJson("/api/v1.1/direct/conversations/{$id}/messages/{$second}")->assertOk();

        expect((string) DmConversation::find($id)->last_message_id)->toBe($first)
            ->and(DmConversationParticipant::where('profile_id', $bob->profile_id)->value('unread_count'))->toBe(1)
            ->and(Notification::where('item_type', DmMessage::class)->where('item_id', $second)->count())->toBe(0);

        Passport::actingAs($bob, ['read', 'write']);
        $this->deleteJson("/api/v1.1/direct/conversations/{$id}/messages/{$first}")->assertNotFound();
    });

    it('lets a participant report a message and nobody else', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();
        $eve = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $message = $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'spam'])->json('id');

        Passport::actingAs($eve, ['read', 'write']);
        $this->postJson('/api/v1.1/report', ['report_type' => 'spam', 'object_type' => 'direct_message', 'object_id' => $message])->assertStatus(400);

        Passport::actingAs($bob, ['read', 'write']);
        $this->postJson('/api/v1.1/report', ['report_type' => 'spam', 'object_type' => 'direct_message', 'object_id' => $message])->assertOk();

        $report = Report::first();

        expect($report->object_type)->toBe(DmMessage::class)
            ->and((string) $report->object_id)->toBe($message)
            ->and($report->reported_profile_id)->toBe($alice->profile_id);
    });
});

describe('federation', function () {
    it('delivers once per server with the fields mastodon needs to thread it as a direct message', function () {
        $alice = dmLocalUser();
        $bob = dmRemoteProfile('bob');
        $carol = dmRemoteProfile('carol');
        $dave = dmRemoteProfile('dave', 'other.example');

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->id, $carol->id, $dave->id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => "hello <b>all</b>\nsecond line"])->assertCreated();

        Queue::assertPushed(DeliverDirectMessageActivity::class, 2);

        $inboxes = [];
        $activity = null;

        Queue::assertPushed(DeliverDirectMessageActivity::class, function ($job) use (&$inboxes, &$activity) {
            $inboxes[] = $job->inbox();
            $activity = $job->activity();

            return true;
        });

        sort($inboxes);
        expect($inboxes)->toBe(['https://other.example/inbox', 'https://remote.example/inbox']);

        $note = $activity['object'];
        $recipients = [$bob->remote_url, $carol->remote_url, $dave->remote_url];

        expect($activity['type'])->toBe('Create')
            ->and($note['type'])->toBe('Note')
            ->and($note['to'])->toEqualCanonicalizing($recipients)
            ->and($note['cc'])->toBe([])
            ->and(collect($note['tag'])->where('type', 'Mention')->pluck('href')->all())->toEqualCanonicalizing($recipients)
            ->and($note['context'])->toBe(DmConversation::localContextUri($id))
            ->and($note['conversation'])->toBe(DmConversation::localContextUri($id))
            ->and($note['inReplyTo'])->toBeNull()
            ->and($note['content'])->toBe("<p>hello &lt;b&gt;all&lt;/b&gt;<br>\nsecond line</p>");

        expect(json_encode($activity))->not->toContain('#Public');
    });

    it('replies into the remote thread it was started from', function () {
        $alice = dmLocalUser();
        $aliceProfile = dmProfile($alice);
        $bob = dmRemoteProfile('bob');
        dmSeedHosts();

        dmDeliver($bob, dmNote($bob, '1', [$aliceProfile]));

        $conversation = DmConversation::first();

        Passport::actingAs($alice, ['read', 'write']);
        $this->postJson("/api/v1.1/direct/conversations/{$conversation->id}/messages", ['message' => 'hi bob'])->assertCreated();
        $this->postJson("/api/v1.1/direct/conversations/{$conversation->id}/messages", ['message' => 'and another'])->assertCreated();

        $notes = [];
        Queue::assertPushed(DeliverDirectMessageActivity::class, function ($job) use (&$notes) {
            $notes[] = $job->activity()['object'];

            return true;
        });

        expect($notes[0]['inReplyTo'])->toBe($bob->remote_url.'/statuses/1')
            ->and($notes[0]['context'])->toBe('https://remote.example/contexts/1')
            ->and($notes[0]['conversation'])->toBe('tag:remote.example,2026-09-21:objectId=1:objectType=Conversation')
            ->and($notes[1]['inReplyTo'])->toBe($notes[0]['id']);
    });

    it('sends media and its description along with the text', function () {
        $alice = dmLocalUser();
        $bob = dmRemoteProfile('bob');
        $media = dmUpload($alice);
        $media->update(['caption' => 'a cat', 'width' => 100, 'height' => 50]);

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), $bob);
        $message = $service->sendMessage($conversation, dmProfile($alice), ['body' => 'look', 'media' => collect([$media])]);

        $note = app(DirectMessageActivityBuilder::class)->buildNote($message, $conversation, dmProfile($alice), collect([$bob]));

        expect($note['attachment'])->toHaveCount(1)
            ->and($note['attachment'][0]['mediaType'])->toBe('image/jpeg')
            ->and($note['attachment'][0]['name'])->toBe('a cat')
            ->and($note['content'])->toBe('<p>look</p>');
    });

    it('tells the other servers when a message is deleted, addressed to them and not the public', function () {
        $alice = dmLocalUser();
        $bob = dmRemoteProfile('bob');

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->id]])->json('id');
        $message = $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'oops'])->json('id');

        $this->deleteJson("/api/v1.1/direct/conversations/{$id}/messages/{$message}")->assertOk();

        Queue::assertPushed(DeliverDirectMessageActivity::class, function ($job) use ($bob, $message) {
            $activity = $job->activity();

            return $activity['type'] === 'Delete'
                && $activity['to'] === [$bob->remote_url]
                && $activity['object']['id'] === DmMessage::localObjectUri($message);
        });
    });

    it('does not federate a conversation between local people', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        Passport::actingAs($alice, ['read', 'write']);
        $id = $this->postJson('/api/v1.1/direct/conversations', ['recipient_ids' => [$bob->profile_id]])->json('id');
        $this->postJson("/api/v1.1/direct/conversations/{$id}/messages", ['message' => 'local'])->assertCreated();

        Queue::assertNotPushed(DeliverDirectMessageActivity::class);
    });
});
