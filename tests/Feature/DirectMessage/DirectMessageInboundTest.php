<?php

use App\Federation\Handlers\DirectMessageHandler;
use App\Federation\Validators\DirectMessageValidator;
use App\Jobs\InboxPipeline\DeleteWorker;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Status;
use App\Models\UserDomainBlock;
use App\Models\UserFilter;
use App\Services\DirectMessageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

require_once __DIR__.'/helpers.php';

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Inbound direct messages
|--------------------------------------------------------------------------
|
| Invariant: a Note addressed only to people is a direct message and never
| anything else. It is stored in the conversation formed by its author and
| the people it names, or it is dropped. It must never reach the code that
| stores posts and replies, because that code files every non-public Note as
| followers-only.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    // Ids minted in the same millisecond only sort by creation order when the
    // worker bits are fixed. Left unset they are random for every id.
    config(['snowflake.datacenter_id' => 1, 'snowflake.worker_id' => 1]);

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

describe('one to one', function () {
    it('stores a direct note as a message in a conversation with its author', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        $message = DmMessage::first();

        expect($message)->not->toBeNull()
            ->and($message->body)->toBe('hello there')
            ->and($message->profile_id)->toBe($alice->id)
            ->and($message->ap_object_uri)->toBe($alice->remote_url.'/statuses/1');

        $conversation = DmConversation::find($message->conversation_id);

        expect($conversation->type)->toBe('dm')
            ->and($conversation->participants_hash)->toBe(DmConversation::dmHash($alice->id, $bob->id))
            ->and($conversation->last_message_id)->toBe($message->id);

        expect(Status::count())->toBe(0);
    });

    it('keeps the thread identifiers the remote server sent', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        $conversation = DmConversation::first();

        expect($conversation->context_uri)->toBe('https://remote.example/contexts/1')
            ->and($conversation->conversation_uri)->toBe('tag:remote.example,2026-09-21:objectId=1:objectType=Conversation');
    });

    it('counts the message as unread and notifies the recipient', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        $participant = DmConversationParticipant::where('profile_id', $bob->id)->first();

        expect($participant->state)->toBe('active')
            ->and($participant->unread_count)->toBe(1)
            ->and($participant->last_activity_at)->not->toBeNull();

        $notification = Notification::where('profile_id', $bob->id)->first();

        expect($notification)->not->toBeNull()
            ->and($notification->action)->toBe('dm')
            ->and($notification->item_type)->toBe(DmMessage::class);
    });

    it('resolves a recipient addressed by an id based actor url', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], ['to' => [url('/users/'.$bob->id)]]));

        expect(DmMessage::count())->toBe(1);
    });

    it('stores a redelivered message once', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmMessage::count())->toBe(1)
            ->and(DmConversationParticipant::where('profile_id', $bob->id)->value('unread_count'))->toBe(1);
    });

    it('does not store a message that already exists as a legacy direct status', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        Status::factory()->create([
            'profile_id' => $alice->id,
            'caption' => 'old',
            'scope' => 'direct',
            'visibility' => 'direct',
            'uri' => $alice->remote_url.'/statuses/1',
        ]);

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmMessage::count())->toBe(0);
    });

    it('turns the conversation into a request when the recipient only takes messages from people they follow', function () {
        $bob = dmProfile(dmLocalUser(publicDm: false));
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmConversationParticipant::where('profile_id', $bob->id)->value('state'))->toBe('request')
            ->and(Notification::where('profile_id', $bob->id)->count())->toBe(0);
    });

    it('skips the request when the recipient follows the sender', function () {
        $bob = dmProfile(dmLocalUser(publicDm: false));
        $alice = dmRemoteProfile();
        dmFollow($bob, $alice);
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmConversationParticipant::where('profile_id', $bob->id)->value('state'))->toBe('active');
    });

    it('stops storing messages once a pending request hits the inbound limit', function () {
        config(['dm.requests.inbound_limit' => 2]);

        $bob = dmProfile(dmLocalUser(publicDm: false));
        $alice = dmRemoteProfile();
        dmSeedHosts();

        foreach (['1', '2', '3'] as $path) {
            dmDeliver($alice, dmNote($alice, $path, [$bob]));
        }

        expect(DmMessage::count())->toBe(2);
    });

    it('drops a message from someone the recipient blocks', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        UserFilter::create([
            'user_id' => $bob->id,
            'filterable_id' => $alice->id,
            'filterable_type' => 'App\Models\Profile',
            'filter_type' => 'block',
        ]);

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmMessage::count())->toBe(0)
            ->and(DmConversation::count())->toBe(0);
    });

    it('drops a message from a domain the recipient blocks', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        UserDomainBlock::create(['profile_id' => $bob->id, 'domain' => 'remote.example']);

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        expect(DmMessage::count())->toBe(0);
    });

    it('does not store a message attributed to someone other than the sender', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $mallory = dmRemoteProfile('mallory', 'other.example');
        dmSeedHosts();

        dmDeliver($mallory, dmNote($alice, '1', [$bob]));

        expect(DmMessage::count())->toBe(0)
            ->and(Status::count())->toBe(0);
    });
});

describe('text and media', function () {
    it('keeps the text of a message that also has media', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], [
            'content' => '<p>look at this</p>',
            'attachment' => [[
                'type' => 'Document',
                'mediaType' => 'image/jpeg',
                'url' => 'https://remote.example/media/1.jpg',
                'name' => 'a cat',
                'blurhash' => 'UBL_:rOpGG-oBUNG,qRj2so|=eE1w^n4S5NH',
                'width' => 1200,
                'height' => 800,
            ]],
        ]));

        $message = DmMessage::with('media')->first();

        expect($message->body)->toBe('look at this')
            ->and($message->type)->toBe('photo')
            ->and($message->media)->toHaveCount(1)
            ->and($message->media[0]->remote_url)->toBe('https://remote.example/media/1.jpg')
            ->and($message->media[0]->status_id)->toBeNull()
            ->and($message->media[0]->caption)->toBe('a cat');
    });

    it('stores a message that is only media', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], [
            'content' => '',
            'attachment' => [
                ['type' => 'Document', 'mediaType' => 'image/jpeg', 'url' => 'https://remote.example/media/1.jpg'],
                ['type' => 'Document', 'mediaType' => 'image/png', 'url' => 'https://remote.example/media/2.png'],
            ],
        ]));

        $message = DmMessage::with('media')->first();

        expect($message->body)->toBeNull()
            ->and($message->type)->toBe('photos')
            ->and($message->media)->toHaveCount(2);
    });

    it('ignores attachments of a type this server does not accept', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], [
            'attachment' => [
                ['type' => 'Document', 'mediaType' => 'application/x-msdownload', 'url' => 'https://remote.example/media/1.exe'],
            ],
        ]));

        $message = DmMessage::with('media')->first();

        expect($message->type)->toBe('text')
            ->and($message->media)->toHaveCount(0);
    });

    it('keeps direct message media out of the unattached media queries', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], [
            'attachment' => [['type' => 'Document', 'mediaType' => 'image/jpeg', 'url' => 'https://remote.example/media/1.jpg']],
        ]));

        expect(Media::whereNull('status_id')->count())->toBe(1)
            ->and(Media::whereNull('status_id')->notInDirectMessage()->count())->toBe(0);
    });
});

describe('plain text', function () {
    it('drops the leading mentions and keeps paragraphs and line breaks', function () {
        $html = '<p><span class="h-card"><a href="https://pixelfed.test/users/bob">@<span>bob</span></a></span> '
            .'<span class="h-card"><a href="https://other.example/users/carol">@<span>carol</span></a></span> first line<br>second line</p>'
            .'<p>new paragraph &amp; more</p>';

        expect(DirectMessageHandler::plainText($html))->toBe("first line\nsecond line\n\nnew paragraph & more");
    });

    it('leaves a mention in the middle of the text alone', function () {
        expect(DirectMessageHandler::plainText('<p>@bob have you met @carol@other.example yet</p>'))
            ->toBe('have you met @carol@other.example yet');
    });

    it('does not keep markup or script content', function () {
        expect(DirectMessageHandler::plainText('<p>hi<script>alert(1)</script> <b>there</b></p>'))->toBe('hi there');
    });

    it('returns null when nothing is left', function () {
        expect(DirectMessageHandler::plainText('<p><span class="h-card"><a href="#">@<span>bob</span></a></span></p>'))->toBeNull();
    });
});

describe('groups', function () {
    it('stores a note addressed to several people as one group conversation', function () {
        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $carol = dmRemoteProfile('carol', 'other.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob, $dave, $carol]));

        $conversation = DmConversation::first();

        expect(DmConversation::count())->toBe(1)
            ->and($conversation->type)->toBe('group')
            ->and($conversation->participants_hash)->toBe(DmConversation::participantsHash([$alice->id, $bob->id, $dave->id, $carol->id]))
            ->and(DmConversationParticipant::where('conversation_id', $conversation->id)->count())->toBe(4)
            ->and(DmMessage::count())->toBe(1);
    });

    it('reads recipients from cc as well as to', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $carol = dmRemoteProfile('carol', 'other.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], ['cc' => [$carol->remote_url]]));

        expect(DmConversation::first()->type)->toBe('group');
    });

    it('never stores a group message as a followers-only post', function () {
        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmFollow($bob, $alice);
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob, $dave], [
            'attachment' => [['type' => 'Document', 'mediaType' => 'image/jpeg', 'url' => 'https://remote.example/media/1.jpg']],
        ]));

        expect(Status::count())->toBe(0)
            ->and(DmMessage::count())->toBe(1);
    });

    it('never stores a group message that replies to a local post as a comment', function () {
        $bobUser = dmLocalUser();
        $bob = dmProfile($bobUser);
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $post = Status::factory()->photo()->create(['profile_id' => $bob->id]);
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob, $dave], ['inReplyTo' => $post->url()]));

        expect(Status::where('in_reply_to_id', $post->id)->count())->toBe(0)
            ->and(DmMessage::count())->toBe(1);
    });

    it('drops a direct note that names nobody on this server instead of storing it as a post', function () {
        dmLocalUser();
        $alice = dmRemoteProfile();
        $carol = dmRemoteProfile('carol', 'other.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$carol], [
            'attachment' => [['type' => 'Document', 'mediaType' => 'image/jpeg', 'url' => 'https://remote.example/media/1.jpg']],
        ]));

        expect(Status::count())->toBe(0)
            ->and(DmMessage::count())->toBe(0);
    });

    it('drops a note addressed to more people than a group allows', function () {
        config(['dm.groups.max_participants' => 3]);

        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $carol = dmRemoteProfile('carol', 'other.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob, $dave, $carol]));

        expect(DmMessage::count())->toBe(0)
            ->and(Status::count())->toBe(0);
    });

    it('hides the message from a member who blocks the sender without telling anyone', function () {
        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        UserFilter::create([
            'user_id' => $dave->id,
            'filterable_id' => $alice->id,
            'filterable_type' => 'App\Models\Profile',
            'filter_type' => 'block',
        ]);

        dmDeliver($alice, dmNote($alice, '1', [$bob, $dave]));

        expect(DmMessage::count())->toBe(1)
            ->and(DmConversationParticipant::where('profile_id', $bob->id)->value('unread_count'))->toBe(1)
            ->and(DmConversationParticipant::where('profile_id', $dave->id)->value('unread_count'))->toBe(0)
            ->and(DmConversationParticipant::where('profile_id', $dave->id)->value('last_activity_at'))->toBeNull()
            ->and(Notification::where('profile_id', $dave->id)->count())->toBe(0);
    });

    it('drops a message when the only person it could reach here has left the group', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $carol = dmNeighbour($alice, dmRemoteProfile('carol', 'other.example'));
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob, $carol]));

        DmConversationParticipant::where('profile_id', $bob->id)->update(['state' => 'left']);

        dmDeliver($alice, dmNote($alice, '2', [$bob, $carol]));

        expect(DmMessage::count())->toBe(1);
    });

    it('starts a separate conversation when the set of people changes', function () {
        $bob = dmProfile(dmLocalUser());
        $dave = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '2', [$bob, $dave]));

        expect(DmConversation::count())->toBe(2);
    });
});

describe('conversation identity', function () {
    it('hashes the same people to the same conversation whatever order they come in', function () {
        // Snowflake ids minted in the same millisecond differ only in their
        // last bits, which a float comparison cannot see
        $a = 1007571621538590723;
        $b = $a + 1;
        $c = $a + 2;

        expect(DmConversation::dmHash($a, $b))->toBe(DmConversation::dmHash($b, $a))
            ->and(DmConversation::participantsHash([$a, $b, $c]))->toBe(DmConversation::participantsHash([$c, $a, $b]))
            ->and(DmConversation::participantsHash([$a, $b, $c]))->toBe(DmConversation::participantsHash([(string) $b, $c, $a, $a]))
            ->and(DmConversation::dmHash($a, $b))->not->toBe(DmConversation::dmHash($a, $c));
    });

    it('keeps one conversation when two participants have neighbouring ids', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $carol = dmNeighbour($alice, dmRemoteProfile('carol', 'other.example'));
        dmSeedHosts();

        dmDeliver($carol, dmNote($carol, '1', [$alice, $bob]));
        dmDeliver($alice, dmNote($alice, '2', [$bob, $carol]));

        $mine = app(DirectMessageService::class)->findOrCreateConversation($bob, collect([$alice, $carol]));

        expect(DmConversation::count())->toBe(1)
            ->and(DmMessage::where('conversation_id', $mine->id)->count())->toBe(2);
    });
});

describe('audience', function () {
    it('does not treat public, unlisted or followers-only notes as direct', function () {
        $alice = dmRemoteProfile();
        $bobUrl = 'https://pixelfed.test/users/bob';

        expect(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => ['https://www.w3.org/ns/activitystreams#Public'], 'cc' => [$bobUrl]], $alice))->toBeFalse()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => [$bobUrl], 'cc' => ['as:Public']], $alice))->toBeFalse()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => [$alice->remote_url.'/followers'], 'cc' => [$bobUrl]], $alice))->toBeFalse()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => ['https://friendica.example/followers/alice'], 'cc' => []], $alice))->toBeFalse()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => $bobUrl], $alice))->toBeTrue()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => [$bobUrl, 'https://other.example/users/carol']], $alice))->toBeTrue()
            ->and(DirectMessageValidator::isDirect(['type' => 'Note', 'to' => []], $alice))->toBeFalse();
    });

    it('still counts a poll vote as a vote', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, [
            'id' => $alice->remote_url.'/votes/1',
            'type' => 'Note',
            'attributedTo' => $alice->remote_url,
            'name' => 'Option A',
            'inReplyTo' => 'https://remote.example/polls/1',
            'to' => [$bob->permalink()],
        ]);

        expect(DmMessage::count())->toBe(0);
    });
});

describe('threading', function () {
    it('links a reply to the message it answers', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '2', [$bob], ['inReplyTo' => $alice->remote_url.'/statuses/1']));

        $first = DmMessage::whereObjectUri($alice->remote_url.'/statuses/1')->first();
        $second = DmMessage::whereObjectUri($alice->remote_url.'/statuses/2')->first();

        expect($second->in_reply_to_id)->toBe($first->id)
            ->and($second->conversation_id)->toBe($first->conversation_id);
    });

    it('follows the remote thread when it moves to a new context', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '2', [$bob], [
            'context' => 'https://remote.example/contexts/2',
            'conversation' => 'tag:remote.example,2026-09-21:objectId=2:objectType=Conversation',
        ]));

        expect(DmConversation::count())->toBe(1)
            ->and(DmConversation::first()->context_uri)->toBe('https://remote.example/contexts/2');
    });
});

describe('edits', function () {
    it('updates the text when the author edits a message', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '1', [$bob], ['content' => '<p>fixed typo</p>']), 'Update');

        $message = DmMessage::first();

        expect(DmMessage::count())->toBe(1)
            ->and($message->body)->toBe('fixed typo')
            ->and($message->edited_at)->not->toBeNull();
    });

    it('ignores an edit from someone who did not write the message', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $mallory = dmRemoteProfile('mallory', 'remote.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($mallory, dmNote($alice, '1', [$bob], ['content' => '<p>hijacked</p>']), 'Update');

        expect(DmMessage::first()->body)->toBe('hello there');
    });
});

describe('deletes', function () {
    it('removes a message when its author deletes it', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($alice, dmNote($alice, '2', [$bob]));

        dmDeliver($alice, ['id' => $alice->remote_url.'/statuses/2', 'type' => 'Tombstone'], 'Delete');

        $conversation = DmConversation::first();
        $first = DmMessage::whereObjectUri($alice->remote_url.'/statuses/1')->first();

        expect(DmMessage::count())->toBe(1)
            ->and($conversation->last_message_id)->toBe($first->id)
            ->and(DmConversationParticipant::where('profile_id', $bob->id)->value('unread_count'))->toBe(1)
            ->and(DB::table('dm_message_media')->count())->toBe(0);
    });

    it('removes the media of a deleted message', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob], [
            'attachment' => [['type' => 'Document', 'mediaType' => 'image/jpeg', 'url' => 'https://remote.example/media/1.jpg']],
        ]));

        expect(Media::count())->toBe(1);

        dmDeliver($alice, ['id' => $alice->remote_url.'/statuses/1', 'type' => 'Tombstone'], 'Delete');

        expect(DmMessage::count())->toBe(0)
            ->and(DB::table('dm_message_media')->count())->toBe(0);

        Queue::assertPushed(MediaDeletePipeline::class, 1);
    });

    it('lets a delete for a direct message through the inbox endpoints', function (string $endpoint) {
        $bobUser = dmLocalUser();
        $bob = dmProfile($bobUser);
        $alice = dmRemoteProfile();
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));

        // What Loops and Mastodon send
        $delete = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $alice->remote_url.'/statuses/1#delete',
            'type' => 'Delete',
            'actor' => $alice->remote_url,
            'to' => [$bob->permalink()],
            'object' => ['id' => $alice->remote_url.'/statuses/1', 'type' => 'Tombstone'],
        ];

        $this->postJson(str_replace('{username}', $bobUser->username, $endpoint), $delete)->assertOk();

        Queue::assertPushed(DeleteWorker::class, 1);
    })->with(['/f/inbox', '/users/{username}/inbox']);

    it('still drops a delete for something this server has never seen', function () {
        $alice = dmRemoteProfile();
        dmSeedHosts();

        $this->postJson('/f/inbox', [
            'id' => $alice->remote_url.'/statuses/404#delete',
            'type' => 'Delete',
            'actor' => $alice->remote_url,
            'object' => ['id' => $alice->remote_url.'/statuses/404', 'type' => 'Tombstone'],
        ])->assertOk();

        Queue::assertNotPushed(DeleteWorker::class);
    });

    it('ignores a delete from someone who did not write the message', function () {
        $bob = dmProfile(dmLocalUser());
        $alice = dmRemoteProfile();
        $mallory = dmRemoteProfile('mallory', 'remote.example');
        dmSeedHosts();

        dmDeliver($alice, dmNote($alice, '1', [$bob]));
        dmDeliver($mallory, ['id' => $alice->remote_url.'/statuses/1', 'type' => 'Tombstone'], 'Delete');

        expect(DmMessage::count())->toBe(1);
    });
});
