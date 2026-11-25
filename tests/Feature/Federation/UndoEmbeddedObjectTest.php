<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['federation.activitypub.enabled' => true]);
});

function undoEmbeddedRemote(): Profile
{
    $domain = 'social.example';
    $actor = "https://{$domain}/user/foo";

    Cache::put(
        'helpers:url:public-ips:v2:'.hash('xxh128', $domain),
        ['state' => 'ok', 'ips' => ['203.0.113.40']],
        3600
    );

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@foo@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'key_id' => "{$actor}#main-key",
        'last_fetched_at' => now(),
    ]);
}

function undoEmbeddedLocal(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function undoEmbeddedShape(string $shape, string $id, string $type): mixed
{
    return match ($shape) {
        'uri' => $id,
        'object' => ['id' => $id, 'type' => $type],
        'list' => [['id' => $id, 'type' => $type]],
    };
}

function undoEmbeddedActivity(Profile $remote, string $type, mixed $object): array
{
    return [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $remote->remote_url.'/undo/338',
        'type' => 'Undo',
        'actor' => $remote->remote_url,
        'object' => [
            'id' => $remote->remote_url.'/'.strtolower($type).'/119',
            'type' => $type,
            'actor' => $remote->remote_url,
            'object' => $object,
        ],
    ];
}

function undoEmbeddedSharedPost(Profile $author, Profile $remote, int $reblogsCount = 1): Status
{
    $original = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'reblogs_count' => $reblogsCount,
    ]);

    Status::factory()->create([
        'profile_id' => $remote->id,
        'reblog_of_id' => $original->id,
        'type' => 'share',
        'local' => false,
    ]);

    return $original;
}

describe('Undo Follow', function () {
    it('removes the follow', function (string $shape) {
        $target = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();

        Follower::create([
            'profile_id' => $remote->id,
            'following_id' => $target->id,
            'local_profile' => false,
        ]);

        (new Inbox([], $target, undoEmbeddedActivity(
            $remote,
            'Follow',
            undoEmbeddedShape($shape, $target->permalink(), 'Person')
        )))->handleUndoActivity();

        expect(Follower::whereProfileId($remote->id)->whereFollowingId($target->id)->exists())->toBeFalse();
    })->with(['uri', 'object', 'list']);

    it('ignores an embedded object without an id', function () {
        $target = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();

        Follower::create([
            'profile_id' => $remote->id,
            'following_id' => $target->id,
            'local_profile' => false,
        ]);

        (new Inbox([], $target, undoEmbeddedActivity(
            $remote,
            'Follow',
            ['type' => 'Person']
        )))->handleUndoActivity();

        expect(Follower::whereProfileId($remote->id)->whereFollowingId($target->id)->exists())->toBeTrue();
    });
});

describe('Undo Announce', function () {
    it('removes the share and decrements the count', function (string $shape) {
        $author = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();
        $original = undoEmbeddedSharedPost($author, $remote);

        (new Inbox([], $author, undoEmbeddedActivity(
            $remote,
            'Announce',
            undoEmbeddedShape($shape, $original->url(), 'Note')
        )))->handleUndoActivity();

        expect(Status::whereProfileId($remote->id)->whereReblogOfId($original->id)->exists())->toBeFalse();
        expect($original->fresh()->reblogs_count)->toBe(0);
    })->with(['uri', 'object', 'list']);

    it('does not decrement the count when there is no share to undo', function () {
        $author = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();

        $original = Status::factory()->create([
            'profile_id' => $author->id,
            'type' => 'photo',
            'reblogs_count' => 3,
        ]);

        (new Inbox([], $author, undoEmbeddedActivity(
            $remote,
            'Announce',
            undoEmbeddedShape('object', $original->url(), 'Note')
        )))->handleUndoActivity();

        expect($original->fresh()->reblogs_count)->toBe(3);
    });

    it('only decrements once when the same Undo is delivered twice', function () {
        $author = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();
        $original = undoEmbeddedSharedPost($author, $remote, 2);

        $undo = undoEmbeddedActivity(
            $remote,
            'Announce',
            undoEmbeddedShape('object', $original->url(), 'Note')
        );

        (new Inbox([], $author, $undo))->handleUndoActivity();
        (new Inbox([], $author, $undo))->handleUndoActivity();

        expect($original->fresh()->reblogs_count)->toBe(1);
    });

    it('ignores an embedded object without an id', function () {
        $author = undoEmbeddedLocal();
        $remote = undoEmbeddedRemote();
        $original = undoEmbeddedSharedPost($author, $remote);

        (new Inbox([], $author, undoEmbeddedActivity(
            $remote,
            'Announce',
            ['type' => 'Note']
        )))->handleUndoActivity();

        expect(Status::whereProfileId($remote->id)->whereReblogOfId($original->id)->exists())->toBeTrue();
        expect($original->fresh()->reblogs_count)->toBe(1);
    });
});
