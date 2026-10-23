<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'federation.activitypub.enabled' => true,
        'exp.cached_home_timeline' => true,
    ]);
});

function uafeedRemoteBooster(): Profile
{
    $domain = 'remote.example';
    $actor = "https://{$domain}/users/booster";

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@booster@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'key_id' => "{$actor}#main-key",
        'last_fetched_at' => now(),
    ]);
}

it('keeps the original post in local followers home feeds when a remote boost is undone', function () {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    $original = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $author->profile_id,
        'local_profile' => true,
    ]);
    FollowerService::add($follower->profile_id, $author->profile_id);

    Redis::del(HomeTimelineService::CACHE_KEY.$follower->profile_id);
    HomeTimelineService::add($follower->profile_id, $original->id);

    $booster = uafeedRemoteBooster();

    (new Inbox([], $author->profile, [
        'id' => $booster->remote_url.'/announce/1/undo',
        'type' => 'Undo',
        'actor' => $booster->remote_url,
        'object' => [
            'id' => $booster->remote_url.'/announce/1',
            'type' => 'Announce',
            'actor' => $booster->remote_url,
            'object' => $original->url(),
        ],
    ]))->handleUndoActivity();

    $feed = HomeTimelineService::get($follower->profile_id, 0, -1);

    expect(collect($feed)->map(fn ($i) => (string) $i))->toContain((string) $original->id);
    expect(Status::whereId($original->id)->exists())->toBeTrue();
});
