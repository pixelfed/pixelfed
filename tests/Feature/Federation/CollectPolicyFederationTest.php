<?php

use App\Jobs\Federation\DeliverActivityChunk;
use App\Jobs\ProfilePipeline\FederateActorUpdate;
use App\Jobs\ProfilePipeline\HandleUpdateActivity;
use App\Jobs\ProfilePipeline\RefreshRemoteProfile;
use App\Models\Collection;
use App\Models\Follower;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\CollectionService;
use App\Services\RemoteActorRefresher;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Validator\UpdatePersonValidator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Redis::flushdb();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function cpfUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function cpfRemote(string $username, array $attrs = []): Profile
{
    $url = "https://remote.example/users/{$username}";

    return Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'username' => "@{$username}@remote.example",
        'remote_url' => $url,
        'inbox_url' => $url.'/inbox',
        'sharedInbox' => 'https://remote.example/inbox',
        'followers_url' => $url.'/followers',
        'last_fetched_at' => now(),
        ...$attrs,
    ]);
}

function cpfRemotePost(Profile $author): Status
{
    $status = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => $author->remote_url.'/statuses/'.uniqid(),
    ]);

    Media::create([
        'status_id' => $status->id,
        'profile_id' => $author->id,
        'remote_media' => true,
        'remote_url' => 'https://remote.example/media/'.$status->id.'.jpg',
        'media_path' => 'https://remote.example/media/'.$status->id.'.jpg',
        'mime' => 'image/jpeg',
        'order' => 1,
    ]);

    return $status;
}

function cpfCollection(User $user): Collection
{
    $collection = new Collection;
    $collection->profile_id = $user->profile_id;
    $collection->title = 'Trip';
    $collection->visibility = 'public';
    $collection->published_at = now();
    $collection->save();

    return $collection;
}

function cpfActor(?array $policy = null, string $username = 'alice'): array
{
    $url = "https://remote.example/users/{$username}";
    $actor = [
        'id' => $url,
        'type' => 'Person',
        'followers' => $url.'/followers',
    ];

    if ($policy !== null) {
        $actor['interactionPolicy'] = $policy;
    }

    return $actor;
}

function cpfFakeRefresher(?Closure $using = null): RemoteActorRefresher
{
    $refresher = new class extends RemoteActorRefresher
    {
        public array $fetched = [];

        public ?Closure $using = null;

        protected function fetch(Profile $profile): ?Profile
        {
            $this->fetched[] = (int) $profile->id;

            return $this->using ? ($this->using)($profile) : null;
        }
    };

    $refresher->using = $using;
    app()->instance(RemoteActorRefresher::class, $refresher);

    return $refresher;
}

describe('actor document', function () {
    it('advertises canCollect next to canFeature', function () {
        $user = cpfUser();

        $actor = FederateActorUpdate::activity($user->profile)['object'];

        expect($actor['interactionPolicy']['canCollect'])->toBe(['automaticApproval' => [CollectionService::AS_PUBLIC]])
            ->and($actor['interactionPolicy'])->toHaveKey('canFeature');

        $context = FederateActorUpdate::activity($user->profile)['@context'][2];
        expect($context['pixelfed'])->toBe('http://pixelfed.org/ns#')
            ->and($context['canCollect'])->toBe(['@id' => 'pixelfed:canCollect', '@type' => '@id']);
    });

    it('maps each setting to an audience', function (string $policy, Closure $expected) {
        $user = cpfUser();
        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
        $settings->can_collect = $policy;
        $settings->save();
        CollectionService::forgetCollectPolicy($user->profile_id);

        $actor = FederateActorUpdate::activity($user->profile->fresh())['object'];

        expect($actor['interactionPolicy']['canCollect']['automaticApproval'])->toBe([$expected($user->profile)]);
    })->with([
        'followers' => ['followers', fn (Profile $p) => $p->permalink('/followers')],
        'nobody' => ['nobody', fn (Profile $p) => $p->permalink()],
    ]);

    it('serves the new value right after the setting changes', function () {
        $user = cpfUser();
        $headers = ['Accept' => 'application/activity+json'];

        $this->get('/users/'.$user->profile->username, $headers)->assertOk();

        $this->actingAs($user->fresh())
            ->post('/settings/privacy', ['can_collect' => 'nobody'])
            ->assertRedirect();

        $actor = json_decode($this->get('/users/'.$user->profile->username, $headers)->getContent(), true);

        expect($actor['interactionPolicy']['canCollect']['automaticApproval'])->toBe([$user->profile->permalink()]);
    });
});

describe('reading a remote actor', function () {
    it('parses canCollect', function (?array $policy, ?string $expected) {
        expect(CollectionService::policyFromActor(cpfActor($policy)))->toBe($expected);
    })->with([
        'no interactionPolicy' => [null, null],
        'no canCollect' => [['canFeature' => ['automaticApproval' => [CollectionService::AS_PUBLIC]]], null],
        'malformed canCollect' => [['canCollect' => 'everyone'], null],
        'public' => [['canCollect' => ['automaticApproval' => [CollectionService::AS_PUBLIC]]], 'everyone'],
        'as:Public' => [['canCollect' => ['automaticApproval' => ['as:Public']]], 'everyone'],
        'single value' => [['canCollect' => ['automaticApproval' => CollectionService::AS_PUBLIC]], 'everyone'],
        'object value' => [['canCollect' => ['automaticApproval' => ['id' => CollectionService::AS_PUBLIC]]], 'everyone'],
        'their followers' => [['canCollect' => ['automaticApproval' => ['https://remote.example/users/alice/followers']]], 'followers'],
        'someone else\'s followers' => [['canCollect' => ['automaticApproval' => ['https://other.example/users/bob/followers']]], 'nobody'],
        'themselves' => [['canCollect' => ['automaticApproval' => ['https://remote.example/users/alice']]], 'nobody'],
        'manual approval only' => [['canCollect' => ['manualApproval' => [CollectionService::AS_PUBLIC]]], 'nobody'],
        'empty' => [['canCollect' => ['automaticApproval' => []]], 'nobody'],
    ]);

    it('stores canCollect when a profile is fetched', function () {
        $data = Helpers::buildProfileData([
            ...cpfActor(['canCollect' => ['automaticApproval' => ['https://remote.example/users/alice/followers']]]),
            'inbox' => 'https://remote.example/users/alice/inbox',
            'publicKey' => ['id' => 'https://remote.example/users/alice#main-key', 'publicKeyPem' => 'key'],
        ], '@alice@remote.example', null);

        expect($data['can_collect'])->toBe('followers');
    });

    it('updates canCollect from Update(Person) and drops the cached policy', function () {
        $alice = cpfRemote('alice');
        expect(CollectionService::collectPolicy($alice->id))->toBe('everyone');

        (new HandleUpdateActivity([
            'type' => 'Update',
            'actor' => $alice->remote_url,
            'object' => [
                ...cpfActor(['canCollect' => ['automaticApproval' => [$alice->remote_url]]]),
                'summary' => 'hi',
                'name' => 'Alice',
            ],
        ]))->handle();

        expect($alice->fresh()->can_collect)->toBe('nobody')
            ->and(CollectionService::collectPolicy($alice->id))->toBe('nobody');
    });
});

describe('adding remote posts', function () {
    it('rejects posts from a remote account that does not allow it', function () {
        cpfFakeRefresher();
        $user = cpfUser();
        $alice = cpfRemote('alice', ['can_collect' => 'nobody']);
        $collection = cpfCollection($user);
        $post = cpfRemotePost($alice);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'This account does not allow their posts to be added to collections.');

        $this->getJson("/api/v1.2/statuses/{$post->id}/collections")
            ->assertOk()
            ->assertJsonPath('meta.can_collect', false);
    });

    it('only lets followers collect when the remote account says followers', function () {
        cpfFakeRefresher();
        $user = cpfUser();
        $alice = cpfRemote('alice', ['can_collect' => 'followers']);
        $collection = cpfCollection($user);
        $post = cpfRemotePost($alice);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Only followers of this account can add their posts to collections.');

        Follower::create(['profile_id' => $user->profile_id, 'following_id' => $alice->id, 'local_profile' => true]);
        Redis::flushdb();

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertOk();
    });

    it('treats remote accounts without the field as open', function () {
        cpfFakeRefresher();
        $user = cpfUser();
        $alice = cpfRemote('alice');
        $collection = cpfCollection($user);
        $post = cpfRemotePost($alice);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertOk()
            ->assertJsonPath('added', [(string) $post->id]);
    });

    it('hides posts already collected once the remote account stops allowing it', function () {
        cpfFakeRefresher();
        $user = cpfUser();
        $alice = cpfRemote('alice');
        $collection = cpfCollection($user);
        $post = cpfRemotePost($alice);
        CollectionService::addStatus($collection, $post, $user->profile);
        Passport::actingAs($user, ['read', 'write']);

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertJsonCount(1, 'data');

        $alice->can_collect = 'nobody';
        $alice->save();

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertJsonCount(0, 'data');

        $alice->can_collect = null;
        $alice->save();

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertJsonCount(1, 'data');
    });
});

describe('refetching stale remote accounts', function () {
    it('refetches a stale author once before deciding', function () {
        $user = cpfUser();
        $alice = cpfRemote('alice', ['last_fetched_at' => now()->subDays(2)]);
        $collection = cpfCollection($user);
        $posts = [cpfRemotePost($alice), cpfRemotePost($alice)];

        $refresher = cpfFakeRefresher(function (Profile $profile) {
            $profile->can_collect = 'nobody';
            $profile->last_fetched_at = now();
            $profile->save();

            return $profile->fresh();
        });

        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => collect($posts)->pluck('id')->all()])
            ->assertStatus(422)
            ->assertJsonPath('rejected.0.error', 'This account does not allow their posts to be added to collections.');

        expect($refresher->fetched)->toBe([$alice->id]);
    });

    it('does not refetch an author fetched in the last day', function () {
        $user = cpfUser();
        $alice = cpfRemote('alice');
        $collection = cpfCollection($user);
        $refresher = cpfFakeRefresher();
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [cpfRemotePost($alice)->id]])
            ->assertOk();

        expect($refresher->fetched)->toBe([]);
    });

    it('caps refetches in one request and queues the rest', function () {
        $user = cpfUser();
        $collection = cpfCollection($user);
        $posts = collect(range(1, 5))->map(fn ($i) => cpfRemotePost(cpfRemote("user{$i}", ['last_fetched_at' => now()->subDays(2)])));
        $refresher = cpfFakeRefresher();
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => $posts->pluck('id')->all()])
            ->assertOk()
            ->assertJsonCount(5, 'added');

        expect($refresher->fetched)->toHaveCount(RemoteActorRefresher::MAX_SYNC_REFRESHES);
        Queue::assertPushed(RefreshRemoteProfile::class, 5 - RemoteActorRefresher::MAX_SYNC_REFRESHES);
    });

    it('falls back to the stored policy and backs off when a refetch fails', function () {
        $alice = cpfRemote('alice', ['last_fetched_at' => now()->subDays(2), 'can_collect' => 'followers']);

        $first = cpfFakeRefresher();
        expect($first->fresh($alice)->can_collect)->toBe('followers')
            ->and($first->fetched)->toBe([$alice->id]);

        $second = cpfFakeRefresher();
        $second->fresh($alice);
        expect($second->fetched)->toBe([]);
    });
});

describe('telling other servers', function () {
    it('sends Update(Person) when the setting changes on the privacy page', function () {
        $user = cpfUser();

        $this->actingAs($user->fresh())->post('/settings/privacy', ['can_collect' => 'followers']);

        Queue::assertPushed(FederateActorUpdate::class, fn ($job) => $job->profileId === $user->profile_id);
    });

    it('sends Update(Person) when the setting changes through the API', function () {
        $user = cpfUser();
        Passport::actingAs($user, ['read', 'write']);

        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'nobody'])->assertOk();

        Queue::assertPushed(FederateActorUpdate::class, fn ($job) => $job->profileId === $user->profile_id);
    });

    it('folds quick changes into the update that is already queued', function () {
        $user = cpfUser();
        Passport::actingAs($user, ['read', 'write']);

        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'nobody'])->assertOk();
        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'followers'])->assertOk();

        Queue::assertPushed(FederateActorUpdate::class, 1);
    });

    it('does not send an update when nothing changed', function () {
        $user = cpfUser();
        Passport::actingAs($user, ['read', 'write']);

        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'everyone'])->assertOk();

        Queue::assertNotPushed(FederateActorUpdate::class);
    });

    it('builds an Update(Person) our own inbox accepts', function () {
        $user = cpfUser();

        $activity = FederateActorUpdate::activity($user->profile);

        expect($activity['type'])->toBe('Update')
            ->and($activity['object']['id'])->toBe($user->profile->permalink())
            ->and(UpdatePersonValidator::validate($activity))->toBeTrue()
            ->and(CollectionService::policyFromActor($activity['object']))->toBe('everyone');
    });

    it('delivers the update to followers\' shared inboxes', function () {
        $user = cpfUser();
        $alice = cpfRemote('alice');
        $bob = cpfRemote('bob');
        Follower::create(['profile_id' => $alice->id, 'following_id' => $user->profile_id]);
        Follower::create(['profile_id' => $bob->id, 'following_id' => $user->profile_id]);

        (new FederateActorUpdate($user->profile_id))->handle();

        Queue::assertPushed(DeliverActivityChunk::class, function ($job) {
            return $job->inboxes() === ['https://remote.example/inbox']
                && $job->activity()['type'] === 'Update';
        });
    });

    it('skips delivery without remote followers', function () {
        $user = cpfUser();

        (new FederateActorUpdate($user->profile_id))->handle();

        Queue::assertNotPushed(DeliverActivityChunk::class);
    });
});
