<?php

use App\Federation\Handlers\DirectMessageHandler;
use App\Jobs\Federation\DeliverDirectMessageActivity;
use App\Models\DirectMessage;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\UserFilter;
use App\Services\DirectMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

require_once __DIR__.'/helpers.php';

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    // Ids minted in the same millisecond only sort by creation order when the
    // worker bits are fixed. Left unset they are random for every id.
    config(['snowflake.datacenter_id' => 1, 'snowflake.worker_id' => 1]);
});

function dmLegacy(Profile $from, Profile $to, string $text, array $dm = [], array $status = []): DirectMessage
{
    $post = Status::factory()->create(array_merge([
        'profile_id' => $from->id,
        'caption' => $text,
        'scope' => 'direct',
        'visibility' => 'direct',
        'in_reply_to_profile_id' => $to->id,
    ], $status));

    $row = new DirectMessage;
    $row->from_id = $from->id;
    $row->to_id = $to->id;
    $row->status_id = $post->id;
    $row->type = $dm['type'] ?? 'text';
    $row->is_hidden = $dm['is_hidden'] ?? false;
    $row->read_at = $dm['read_at'] ?? null;
    $row->meta = $dm['meta'] ?? null;
    $row->save();

    return $row;
}

describe('dm:backfill-conversations', function () {
    it('turns a legacy thread into one conversation and keeps the status ids', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser());

        $one = dmLegacy($alice, $bob, 'first', ['read_at' => now()]);
        $two = dmLegacy($bob, $alice, 'second', ['read_at' => now()]);
        $three = dmLegacy($alice, $bob, 'third');

        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();

        $conversation = DmConversation::first();

        expect(DmConversation::count())->toBe(1)
            ->and($conversation->participants_hash)->toBe(DmConversation::dmHash($alice->id, $bob->id))
            ->and($conversation->last_message_id)->toBe($three->status_id)
            ->and(DmMessage::orderBy('id')->pluck('id')->all())->toBe([$one->status_id, $two->status_id, $three->status_id])
            ->and(DmMessage::orderBy('id')->pluck('body')->all())->toBe(['first', 'second', 'third'])
            ->and(DmMessage::find($one->status_id)->legacy_dm_id)->toBe($one->id);

        $bobState = DmConversationParticipant::where('profile_id', $bob->id)->first();
        $aliceState = DmConversationParticipant::where('profile_id', $alice->id)->first();

        expect($bobState->unread_count)->toBe(1)
            ->and($bobState->last_read_message_id)->toBe($one->status_id)
            ->and($bobState->last_activity_at)->not->toBeNull()
            ->and($aliceState->unread_count)->toBe(0)
            ->and($aliceState->last_read_message_id)->toBe($three->status_id);
    });

    it('can be run again without duplicating anything', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser());

        dmLegacy($alice, $bob, 'first');

        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();
        dmLegacy($bob, $alice, 'second');
        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();
        $this->artisan('dm:backfill-conversations', ['--force' => true, '--full' => true])->assertSuccessful();

        expect(DmMessage::count())->toBe(2)
            ->and(DmConversation::count())->toBe(1)
            ->and(DmConversationParticipant::count())->toBe(2);
    });

    it('carries over media, filtered threads, story metadata and mutes', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser());
        $carol = dmRemoteProfile('carol');

        $photo = dmLegacy($alice, $bob, 'with a photo', ['type' => 'photo']);
        $media = Media::create([
            'status_id' => $photo->status_id,
            'profile_id' => $alice->id,
            'media_path' => 'public/m/legacy.jpg',
            'mime' => 'image/jpeg',
            'size' => 1000,
        ]);

        dmLegacy($carol, $bob, 'Tom &amp; Jerry', ['is_hidden' => true], ['uri' => $carol->remote_url.'/statuses/9']);

        dmLegacy($alice, $bob, '🔥', [
            'type' => 'story:react',
            'meta' => json_encode(['story_id' => 5, 'reaction' => '🔥']),
        ], ['type' => 'story:reaction']);

        UserFilter::create([
            'user_id' => $bob->id,
            'filterable_id' => $alice->id,
            'filterable_type' => 'App\Models\Profile',
            'filter_type' => 'dm.mute',
        ]);

        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();

        $withPhoto = DmMessage::with('media')->find($photo->status_id);
        $fromCarol = DmMessage::where('profile_id', $carol->id)->first();
        $reaction = DmMessage::where('type', 'story:react')->first();

        expect($withPhoto->media->pluck('id')->all())->toBe([$media->id])
            ->and($withPhoto->body)->toBe('with a photo')
            ->and($fromCarol->body)->toBe('Tom & Jerry')
            ->and($fromCarol->ap_object_uri)->toBe($carol->remote_url.'/statuses/9')
            ->and(DmMessage::whereObjectUri($carol->remote_url.'/statuses/9')->exists())->toBeTrue()
            ->and($reaction->meta)->toBe(['story_id' => 5, 'reaction' => '🔥']);

        $carolThread = DmConversation::where('participants_hash', DmConversation::dmHash($carol->id, $bob->id))->first();
        $aliceThread = DmConversation::where('participants_hash', DmConversation::dmHash($alice->id, $bob->id))->first();

        expect(DmConversationParticipant::where('conversation_id', $carolThread->id)->where('profile_id', $bob->id)->value('state'))->toBe('request')
            ->and(DmConversationParticipant::where('conversation_id', $aliceThread->id)->where('profile_id', $bob->id)->value('muted_at'))->not->toBeNull();
    });
});

describe('remote deletes of converted messages', function () {
    it('hands a converted message back to the status path so the legacy status goes too', function () {
        $bob = dmProfile(dmLocalUser());
        $carol = dmRemoteProfile('carol');

        dmLegacy($carol, $bob, 'old one', [], ['uri' => $carol->remote_url.'/statuses/9']);
        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();

        $handler = app(DirectMessageHandler::class);

        expect($handler->handleDelete($carol, $carol->remote_url.'/statuses/9'))->toBeFalse()
            ->and(DmMessage::count())->toBe(0);
    });

    it('fully handles a message that never was a status', function () {
        $bob = dmProfile(dmLocalUser());
        $carol = dmRemoteProfile('carol');

        $service = app(DirectMessageService::class);
        $service->storeMessage($service->findOrCreateDm($carol, $bob), $carol, [
            'body' => 'new one',
            'ap_object_uri' => $carol->remote_url.'/statuses/10',
        ]);

        expect(app(DirectMessageHandler::class)->handleDelete($carol, $carol->remote_url.'/statuses/10'))->toBeTrue()
            ->and(DmMessage::count())->toBe(0);
    });
});

describe('cleanup', function () {
    it('removes the message when the status behind it is deleted', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser());

        $legacy = dmLegacy($alice, $bob, 'first');
        $this->artisan('dm:backfill-conversations', ['--force' => true])->assertSuccessful();

        app(DirectMessageService::class)->deleteByStatusId($legacy->status_id);

        expect(DmMessage::count())->toBe(0)
            ->and(DmConversation::first()->last_message_id)->toBeNull();
    });

    it('clears a deleted profile out of its conversations', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());

        $service = app(DirectMessageService::class);

        $direct = $service->findOrCreateDm($alice, $bob);
        $service->sendMessage($direct, $alice, ['body' => 'one to one']);
        $service->sendMessage($direct, $bob, ['body' => 'reply']);

        $group = $service->findOrCreateConversation($alice, collect([$bob, $dave]));
        $service->sendMessage($group, $alice, ['body' => 'from alice']);
        $service->sendMessage($group, $bob, ['body' => 'from bob']);

        $service->purgeProfile($alice->id);

        expect(DmConversation::find($direct->id))->toBeNull()
            ->and(DmMessage::where('conversation_id', $direct->id)->count())->toBe(0)
            ->and(DmMessage::where('conversation_id', $group->id)->pluck('body')->all())->toBe(['from bob'])
            ->and(DmConversationParticipant::where('conversation_id', $group->id)->where('profile_id', $alice->id)->value('state'))->toBe('left');
    });

    it('keeps direct message media away from the orphan media collector', function () {
        $alice = dmLocalUser();
        $bob = dmLocalUser();

        $attached = Media::create(['profile_id' => $alice->profile_id, 'user_id' => $alice->id, 'media_path' => 'public/m/a.jpg', 'mime' => 'image/jpeg', 'size' => 1]);
        $orphan = Media::create(['profile_id' => $alice->profile_id, 'user_id' => $alice->id, 'media_path' => 'public/m/b.jpg', 'mime' => 'image/jpeg', 'size' => 1]);

        DB::table('media')->whereIn('id', [$attached->id, $orphan->id])->update(['created_at' => now()->subDay()]);

        $service = app(DirectMessageService::class);
        $conversation = $service->findOrCreateDm(dmProfile($alice), dmProfile($bob));
        $service->sendMessage($conversation, dmProfile($alice), ['media' => collect([$attached])]);

        $collectable = Media::whereNull('status_id')
            ->notInDirectMessage()
            ->where('created_at', '<', now()->subHours(2))
            ->pluck('id')
            ->all();

        expect($collectable)->toBe([$orphan->id]);
    });
});

describe('story replies', function () {
    it('lands in the conversation with the story author', function () {
        $alice = dmProfile(dmLocalUser());
        $bob = dmProfile(dmLocalUser(publicDm: false));

        $message = app(DirectMessageService::class)->storeStoryMessage(
            $alice,
            $bob,
            'story:comment',
            'nice one',
            ['story_id' => 1, 'caption' => 'nice one'],
            12345
        );

        $state = DmConversationParticipant::where('profile_id', $bob->id)->first();

        expect($message->type)->toBe('story:comment')
            ->and($message->status_id)->toBe(12345)
            ->and($message->meta['caption'])->toBe('nice one')
            ->and($state->state)->toBe('active')
            ->and($state->unread_count)->toBe(1)
            ->and(Notification::where('profile_id', $bob->id)->where('action', 'story:comment')->count())->toBe(1);

        // The story pipeline federates these, not the direct message one
        Queue::assertNotPushed(DeliverDirectMessageActivity::class);
    });
});
