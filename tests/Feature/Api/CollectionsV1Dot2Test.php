<?php

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionItemRemoval;
use App\Models\Follower;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserFilter;
use App\Models\UserSetting;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::flushdb();
    config(['instance.enable_cc' => false]);
});

function v12User(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function v12Post(int $profileId, string $scope = 'public', string $type = 'photo'): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => $type,
        'scope' => $scope,
        'visibility' => $scope,
    ]);

    $profile = Profile::find($profileId);

    Media::create([
        'status_id' => $status->id,
        'profile_id' => $profileId,
        'user_id' => $profile->user_id,
        'media_path' => 'public/m/_v2/'.$profileId.'/'.$status->id.'.jpg',
        'mime' => 'image/jpeg',
        'order' => 1,
    ]);

    return $status;
}

function v12Collection(User $user, array $attrs = []): Collection
{
    $collection = new Collection;
    $collection->profile_id = $user->profile_id;
    $collection->title = $attrs['title'] ?? 'Trip';
    $collection->visibility = $attrs['visibility'] ?? 'public';
    $collection->sort = $attrs['sort'] ?? 'oldest';
    $collection->published_at = array_key_exists('published_at', $attrs) ? $attrs['published_at'] : now();
    $collection->save();

    return $collection;
}

function v12Policy(User $user, string $policy): void
{
    $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
    $settings->can_collect = $policy;
    $settings->save();
    CollectionService::forgetCollectPolicy($user->profile_id);
}

function v12Block(User $by, User $target): void
{
    UserFilter::create([
        'user_id' => $by->profile_id,
        'filterable_id' => $target->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
    ]);
    Redis::flushdb();
}

describe('creating and editing', function () {
    it('creates a collection', function () {
        $user = v12User();
        Passport::actingAs($user, ['read', 'write']);

        $res = $this->postJson('/api/v1.2/collections', [
            'title' => '<b>Summer</b> 2026',
            'description' => 'Beach days',
            'sort' => 'recent',
        ])->assertCreated()
            ->assertJsonPath('title', 'Summer 2026')
            ->assertJsonPath('visibility', 'public')
            ->assertJsonPath('sort', 'recent')
            ->assertJsonPath('post_count', 0)
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('max_posts', 1000)
            ->json();

        expect($res['published_at'])->not->toBeNull();
        expect(Collection::find($res['id'])->profile_id)->toBe($user->profile_id);
    });

    it('creates an unpublished draft', function () {
        $user = v12User();
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson('/api/v1.2/collections', ['title' => 'Later', 'visibility' => 'draft'])
            ->assertCreated()
            ->assertJsonPath('published_at', null)
            ->assertJsonPath('visibility', 'draft');
    });

    it('seeds a new collection with posts', function () {
        $user = v12User();
        $post = v12Post($user->profile_id);
        $private = v12Post($user->profile_id, 'private');
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson('/api/v1.2/collections', [
            'title' => 'Seeded',
            'status_ids' => [$post->id, $private->id],
        ])->assertCreated()
            ->assertJsonPath('post_count', 1)
            ->assertJsonPath('rejected.0.status_id', (string) $private->id);
    });

    it('requires a title', function () {
        $user = v12User();
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson('/api/v1.2/collections', ['title' => '<p></p>'])->assertStatus(422);
        $this->postJson('/api/v1.2/collections', [])->assertStatus(422);
    });

    it('requires the write scope', function () {
        $user = v12User();
        Passport::actingAs($user, ['read']);

        $this->postJson('/api/v1.2/collections', ['title' => 'Nope'])->assertForbidden();
    });

    it('publishes a draft when its visibility changes', function () {
        $user = v12User();
        $collection = v12Collection($user, ['visibility' => 'draft', 'published_at' => null]);
        Passport::actingAs($user, ['read', 'write']);

        $this->patchJson("/api/v1.2/collections/{$collection->id}", ['visibility' => 'private', 'sort' => 'recent'])
            ->assertOk()
            ->assertJsonPath('visibility', 'private')
            ->assertJsonPath('sort', 'recent');

        expect($collection->fresh()->published_at)->not->toBeNull();
    });

    it('does not let you edit someone else\'s collection', function () {
        $owner = v12User();
        $other = v12User();
        $collection = v12Collection($owner);
        Passport::actingAs($other, ['read', 'write']);

        $this->patchJson("/api/v1.2/collections/{$collection->id}", ['title' => 'Mine now'])
            ->assertNotFound()
            ->assertJsonPath('error', 'Record not found');
        $this->deleteJson("/api/v1.2/collections/{$collection->id}")->assertNotFound();
    });

    it('deletes a collection with its items and removals', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $post = v12Post($user->profile_id);
        CollectionService::addStatus($collection, $post, $user->profile);
        CollectionItemRemoval::create(['collection_id' => $collection->id, 'status_id' => 1, 'profile_id' => 1]);
        Passport::actingAs($user, ['read', 'write']);

        $this->deleteJson("/api/v1.2/collections/{$collection->id}")->assertOk()->assertJsonPath('deleted', true);

        expect(Collection::find($collection->id))->toBeNull();
        expect(CollectionItem::whereCollectionId($collection->id)->count())->toBe(0);
        expect(CollectionItemRemoval::whereCollectionId($collection->id)->count())->toBe(0);
    });
});

describe('items', function () {
    it('adds your own posts and sorts by date added', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $a = v12Post($user->profile_id);
        $b = v12Post($user->profile_id, 'unlisted');
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$a->id]])
            ->assertOk()
            ->assertJsonPath('added', [(string) $a->id]);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$b->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('added', [(string) $b->id])
            ->assertJsonPath('existing', [(string) $a->id])
            ->assertJsonPath('collection.post_count', 2);

        $oldest = $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertOk()->json();
        expect($oldest['meta']['sort'])->toBe('oldest');
        expect(array_column(array_column($oldest['data'], 'status'), 'id'))->toBe([(string) $a->id, (string) $b->id]);

        $recent = $this->getJson("/api/v1.2/collections/{$collection->id}/items?sort=recent")->assertOk()->json();
        expect(array_column(array_column($recent['data'], 'status'), 'id'))->toBe([(string) $b->id, (string) $a->id]);
        expect($recent['data'][0])->toHaveKeys(['id', 'added_at', 'status']);
        expect($recent['data'][0]['status'])->toHaveKeys(['favourited', 'bookmarked', 'reblogged']);
    });

    it('keeps the add sequence for posts added in one request', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $posts = collect(range(1, 8))->map(fn () => v12Post($user->profile_id));
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => $posts->pluck('id')->all()])
            ->assertOk();

        $ids = $posts->map(fn ($p) => (string) $p->id)->all();

        $oldest = $this->getJson("/api/v1.2/collections/{$collection->id}/items?limit=3")->assertOk()->json();
        $next = $this->getJson("/api/v1.2/collections/{$collection->id}/items?limit=3&cursor={$oldest['meta']['next_cursor']}")->assertOk()->json();
        expect(array_column(array_column(array_merge($oldest['data'], $next['data']), 'status'), 'id'))->toBe(array_slice($ids, 0, 6));

        $recent = $this->getJson("/api/v1.2/collections/{$collection->id}/items?sort=recent&limit=8")->assertOk()->json();
        expect(array_column(array_column($recent['data'], 'status'), 'id'))->toBe(array_reverse($ids));
    });

    it('uses the collection sort by default', function () {
        $user = v12User();
        $collection = v12Collection($user, ['sort' => 'recent']);
        $a = v12Post($user->profile_id);
        $b = v12Post($user->profile_id);
        CollectionService::addStatus($collection, $a, $user->profile);
        CollectionService::addStatus($collection, $b, $user->profile);
        Passport::actingAs($user, ['read']);

        $res = $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertOk()->json();

        expect($res['meta']['sort'])->toBe('recent');
        expect($res['data'][0]['status']['id'])->toBe((string) $b->id);
    });

    it('rejects followers-only and text posts', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $private = v12Post($user->profile_id, 'private');
        $text = Status::factory()->create(['profile_id' => $user->profile_id]);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$private->id, $text->id]])
            ->assertStatus(422)
            ->assertJsonCount(2, 'rejected');
    });

    it('enforces the item limit', function () {
        config(['pixelfed.max_collection_length' => 2]);
        $user = v12User();
        $collection = v12Collection($user);
        $posts = collect(range(1, 3))->map(fn () => v12Post($user->profile_id));
        Passport::actingAs($user, ['read', 'write']);

        $res = $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => $posts->pluck('id')->all()])
            ->assertOk()
            ->json();

        expect($res['added'])->toHaveCount(2);
        expect($res['rejected'][0]['error'])->toContain('full');
    });

    it('caps the limit at 1000 whatever the config says', function () {
        config(['pixelfed.max_collection_length' => 5000]);
        expect(CollectionService::maxItems())->toBe(1000);

        config(['pixelfed.max_collection_length' => 250]);
        expect(CollectionService::maxItems())->toBe(250);
    });

    it('keeps order contiguous for legacy readers when removing', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $posts = collect(range(1, 3))->map(fn () => v12Post($user->profile_id));
        $posts->each(fn ($p) => CollectionService::addStatus($collection, $p, $user->profile));
        Passport::actingAs($user, ['read', 'write']);

        $this->deleteJson("/api/v1.2/collections/{$collection->id}/items/{$posts[0]->id}")
            ->assertOk()
            ->assertJsonPath('collection.post_count', 2);

        expect(CollectionItem::whereCollectionId($collection->id)->orderBy('order')->pluck('order')->all())->toBe([0, 1]);

        $this->deleteJson("/api/v1.2/collections/{$collection->id}/items/{$posts[0]->id}")->assertNotFound();
    });
});

describe('other people\'s posts', function () {
    it('adds another account\'s public post', function () {
        $user = v12User();
        $author = v12User();
        $collection = v12Collection($user);
        $post = v12Post($author->profile_id);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertOk()
            ->assertJsonPath('added', [(string) $post->id]);

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")
            ->assertOk()
            ->assertJsonPath('data.0.status.id', (string) $post->id);
    });

    it('adds a public post from a remote account', function () {
        $user = v12User();
        $remote = Profile::factory()->remote()->create();
        $collection = v12Collection($user);
        $post = Status::factory()->create(['profile_id' => $remote->id, 'type' => 'photo', 'local' => false]);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertOk()
            ->assertJsonPath('added', [(string) $post->id]);
    });

    it('rejects another account\'s unlisted post', function () {
        $user = v12User();
        $author = v12User();
        $collection = v12Collection($user);
        $post = v12Post($author->profile_id, 'unlisted');
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Only public posts from other accounts can be added to collections.');
    });

    it('respects a nobody policy, and hides posts already collected', function () {
        $user = v12User();
        $author = v12User();
        $collection = v12Collection($user);
        $kept = v12Post($author->profile_id);
        CollectionService::addStatus($collection, $kept, $user->profile);
        $post = v12Post($author->profile_id);
        v12Policy($author, 'nobody');
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'This account does not allow their posts to be added to collections.');

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/pixelfed/v1/collections/items/{$collection->id}")->assertOk()->assertJsonCount(0);
    });

    it('respects a followers policy', function () {
        $user = v12User();
        $author = v12User();
        $collection = v12Collection($user);
        $post = v12Post($author->profile_id);
        v12Policy($author, 'followers');
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422);

        Follower::create(['profile_id' => $user->profile_id, 'following_id' => $author->profile_id, 'local_profile' => true]);
        Redis::flushdb();

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertOk();
    });

    it('stops collecting and hides items when the author blocks the collector', function () {
        $user = v12User();
        $author = v12User();
        $collection = v12Collection($user);
        $kept = v12Post($author->profile_id);
        CollectionService::addStatus($collection, $kept, $user->profile);
        $post = v12Post($author->profile_id);
        v12Block($author, $user);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422);

        $this->getJson("/api/v1.2/collections/{$collection->id}/items")->assertOk()->assertJsonCount(0, 'data');
    });
});

describe('the author\'s side', function () {
    it('lets the author see and remove their post from others\' collections', function () {
        $collector = v12User();
        $author = v12User();
        $collection = v12Collection($collector);
        $post = v12Post($author->profile_id);
        CollectionService::addStatus($collection, $post, $collector->profile);

        Passport::actingAs($author, ['read', 'write']);

        $this->getJson("/api/v1.2/statuses/{$post->id}/collected_by")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (string) $collection->id);

        $this->deleteJson("/api/v1.2/statuses/{$post->id}/collected_by/{$collection->id}")
            ->assertOk()
            ->assertJsonPath('removed', 1);

        expect(CollectionItem::whereCollectionId($collection->id)->count())->toBe(0);

        Passport::actingAs($collector, ['read', 'write']);

        $this->postJson("/api/v1.2/collections/{$collection->id}/items", ['status_ids' => [$post->id]])
            ->assertStatus(422)
            ->assertJsonPath('rejected.0.error', 'The author removed this post from this collection.');
    });

    it('removes a post from every other collection at once', function () {
        $author = v12User();
        $post = v12Post($author->profile_id);
        $own = v12Collection($author);
        CollectionService::addStatus($own, $post, $author->profile);

        foreach (range(1, 3) as $i) {
            $collector = v12User();
            CollectionService::addStatus(v12Collection($collector), $post, $collector->profile);
        }

        Passport::actingAs($author, ['read', 'write']);

        $this->deleteJson("/api/v1.2/statuses/{$post->id}/collected_by")
            ->assertOk()
            ->assertJsonPath('removed', 3);

        expect(CollectionItem::whereObjectId($post->id)->pluck('collection_id')->all())->toBe([$own->id]);
        expect(CollectionItemRemoval::whereStatusId($post->id)->count())->toBe(3);
    });

    it('only lets the author use collected_by', function () {
        $author = v12User();
        $other = v12User();
        $post = v12Post($author->profile_id);
        Passport::actingAs($other, ['read', 'write']);

        $this->getJson("/api/v1.2/statuses/{$post->id}/collected_by")->assertNotFound();
        $this->deleteJson("/api/v1.2/statuses/{$post->id}/collected_by")->assertNotFound();
    });

    it('reads and updates the canCollect setting', function () {
        $user = v12User();
        Passport::actingAs($user, ['read', 'write']);

        $this->getJson('/api/v1.2/collections/settings')->assertOk()->assertJsonPath('can_collect', 'everyone');
        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'followers'])->assertOk()->assertJsonPath('can_collect', 'followers');
        $this->putJson('/api/v1.2/collections/settings', ['can_collect' => 'nope'])->assertStatus(422);

        expect(CollectionService::collectPolicy($user->profile_id))->toBe('followers');
    });
});

describe('a post across your collections', function () {
    it('flags which collections contain a post and updates several at once', function () {
        $user = v12User();
        $a = v12Collection($user, ['title' => 'A']);
        $b = v12Collection($user, ['title' => 'B']);
        $c = v12Collection($user, ['title' => 'C']);
        $post = v12Post($user->profile_id);
        CollectionService::addStatus($a, $post, $user->profile);
        Passport::actingAs($user, ['read', 'write']);

        $res = $this->getJson("/api/v1.2/statuses/{$post->id}/collections")->assertOk()->json();
        expect($res['meta']['can_collect'])->toBeTrue();
        expect(collect($res['data'])->pluck('contains', 'id')->sortKeys()->all())->toBe(collect([
            (string) $c->id => false,
            (string) $b->id => false,
            (string) $a->id => true,
        ])->sortKeys()->all());

        $res = $this->postJson("/api/v1.2/statuses/{$post->id}/collections", [
            'add' => [$b->id, $c->id],
            'remove' => [$a->id],
        ])->assertOk()->json();

        expect($res['added'])->toBe([(string) $b->id, (string) $c->id]);
        expect($res['removed'])->toBe([(string) $a->id]);
        expect(collect($res['collection_ids'])->sort()->values()->all())->toBe(collect([(string) $b->id, (string) $c->id])->sort()->values()->all());
    });

    it('does not touch collections you do not own', function () {
        $user = v12User();
        $other = v12User();
        $theirs = v12Collection($other);
        $post = v12Post($user->profile_id);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1.2/statuses/{$post->id}/collections", ['add' => [$theirs->id]])
            ->assertStatus(422)
            ->assertJsonPath('rejected.0.error', 'Collection not found.');

        expect(CollectionItem::whereCollectionId($theirs->id)->count())->toBe(0);
    });
});

describe('visibility', function () {
    it('hides private collections from non-followers and drafts from everyone else', function () {
        $owner = v12User();
        $viewer = v12User();
        $public = v12Collection($owner);
        $private = v12Collection($owner, ['visibility' => 'private']);
        $draft = v12Collection($owner, ['visibility' => 'draft', 'published_at' => null]);
        Passport::actingAs($viewer, ['read']);

        $this->getJson("/api/v1.2/collections/{$public->id}")->assertOk();
        $this->getJson("/api/v1.2/collections/{$private->id}")->assertNotFound();
        $this->getJson("/api/v1.2/collections/{$draft->id}")->assertNotFound();

        $this->getJson("/api/v1.2/accounts/{$owner->profile_id}/collections")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $public->id);

        Follower::create(['profile_id' => $viewer->profile_id, 'following_id' => $owner->profile_id, 'local_profile' => true]);
        Redis::flushdb();

        $this->getJson("/api/v1.2/collections/{$private->id}")->assertOk();
        $this->getJson("/api/v1.2/accounts/{$owner->profile_id}/collections")->assertOk()->assertJsonCount(2, 'data');
    });

    it('lists your own drafts', function () {
        $user = v12User();
        v12Collection($user);
        v12Collection($user, ['visibility' => 'draft', 'published_at' => null]);
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1.2/collections/self')->assertOk()->assertJsonCount(2, 'data');
    });

    it('hides collections from accounts the owner blocks', function () {
        $owner = v12User();
        $viewer = v12User();
        $collection = v12Collection($owner);
        v12Block($owner, $viewer);
        Passport::actingAs($viewer, ['read']);

        $this->getJson("/api/v1.2/collections/{$collection->id}")->assertNotFound();
        $this->getJson("/api/v1.2/accounts/{$owner->profile_id}/collections")->assertNotFound();
    });
});

describe('compose', function () {
    it('adds a new post to several collections from POST /api/v1/statuses', function () {
        Bus::fake();
        $user = v12User();
        $user->last_active_at = now();
        $user->save();

        $a = v12Collection($user);
        $b = v12Collection($user);
        $theirs = v12Collection(v12User());

        $media = Media::create([
            'profile_id' => $user->profile_id,
            'user_id' => $user->id,
            'media_path' => 'public/m/_v2/'.$user->profile_id.'/compose.jpg',
            'mime' => 'image/jpeg',
            'order' => 1,
        ]);

        Passport::actingAs($user, ['read', 'write']);

        $res = $this->postJson('/api/v1/statuses', [
            'status' => 'hello',
            'media_ids' => [$media->id],
            'collection_ids' => [$a->id, $b->id, $theirs->id],
        ])->assertOk()->json();

        expect(CollectionItem::whereObjectId($res['id'])->pluck('collection_id')->sort()->values()->all())
            ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
    });

    it('skips collections for a followers-only post', function () {
        Bus::fake();
        $user = v12User();
        $user->last_active_at = now();
        $user->save();
        $collection = v12Collection($user);

        $media = Media::create([
            'profile_id' => $user->profile_id,
            'user_id' => $user->id,
            'media_path' => 'public/m/_v2/'.$user->profile_id.'/private.jpg',
            'mime' => 'image/jpeg',
            'order' => 1,
        ]);

        Passport::actingAs($user, ['read', 'write']);

        $this->postJson('/api/v1/statuses', [
            'media_ids' => [$media->id],
            'visibility' => 'private',
            'collection_ids' => [$collection->id],
        ])->assertOk();

        expect(CollectionItem::whereCollectionId($collection->id)->count())->toBe(0);
    });
});

describe('legacy endpoints', function () {
    it('still serves collections created by the new API', function () {
        $user = v12User();
        $collection = v12Collection($user);
        $post = v12Post($user->profile_id);
        CollectionService::addStatus($collection, $post, $user->profile);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/pixelfed/v1/collections/view/{$collection->id}")
            ->assertOk()
            ->assertJsonPath('post_count', 1);

        $this->getJson("/api/pixelfed/v1/collections/items/{$collection->id}")
            ->assertOk()
            ->assertJsonPath('0.id', (string) $post->id);
    });
});
