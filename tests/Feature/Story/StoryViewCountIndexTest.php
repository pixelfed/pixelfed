<?php

use App\Models\Profile;
use App\Models\Story;
use App\Models\StoryView;
use App\Services\StoryIndexService;
use App\Services\StoryViewService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

function makeViewCountStory(Profile $author): Story
{
    $story = new Story;
    $story->profile_id = $author->id;
    $story->type = 'photo';
    $story->duration = 3;
    $story->path = 'public/story/'.uniqid().'.jpg';
    $story->local = true;
    $story->active = true;
    $story->public = false;
    $story->view_count = 0;
    $story->expires_at = now()->addHours(24);
    $story->save();

    return $story->refresh();
}

function authorCarouselViewCount(Profile $author, Story $story): ?int
{
    $nodes = app(StoryIndexService::class)->fetchCarouselNodes(
        (int) $author->id,
        fn (array $ids) => collect($ids)->mapWithKeys(fn ($id) => [$id => [
            'id' => (string) $id,
            'username' => 'user'.$id,
            'acct' => 'user'.$id,
            'avatar' => null,
            'local' => true,
        ]])->all()
    );

    $item = collect($nodes)
        ->where('is_author', true)
        ->pluck('stories')
        ->flatten(1)
        ->firstWhere('id', (string) $story->id);

    return $item['view_count'] ?? null;
}

beforeEach(function () {
    Redis::flushdb();
});

afterEach(function () {
    Redis::flushdb();
});

it('reflects new views in the author carousel without reindexing', function () {
    $author = Profile::factory()->create(['user_id' => null]);
    $local = Profile::factory()->create(['user_id' => null]);
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $story = makeViewCountStory($author);

    app(StoryIndexService::class)->indexStory($story);

    expect(authorCarouselViewCount($author, $story))->toBe(0);

    $views = app(StoryViewService::class);

    expect($views->record($story, (int) $local->id))->toBeTrue();
    expect($views->record($story, (int) $remote->id))->toBeTrue();

    expect(authorCarouselViewCount($author, $story))->toBe(2);
    expect($story->fresh()->view_count)->toBe(2);
    expect(StoryView::whereStoryId($story->id)->count())->toBe(2);
});

it('counts a repeat view once and marks the story seen for the viewer', function () {
    $author = Profile::factory()->create(['user_id' => null]);
    $viewer = Profile::factory()->create(['user_id' => null]);
    $story = makeViewCountStory($author);

    $index = app(StoryIndexService::class);
    $index->indexStory($story);

    $views = app(StoryViewService::class);

    expect($views->record($story, (int) $viewer->id))->toBeTrue();
    expect($views->record($story, (int) $viewer->id))->toBeFalse();

    expect(authorCarouselViewCount($author, $story))->toBe(1);
    expect($story->fresh()->view_count)->toBe(1);
    expect($index->hasSeen((int) $viewer->id, (int) $story->id))->toBeTrue();
});

it('ignores the author viewing their own story', function () {
    $author = Profile::factory()->create(['user_id' => null]);
    $story = makeViewCountStory($author);

    app(StoryIndexService::class)->indexStory($story);

    expect(app(StoryViewService::class)->record($story, (int) $author->id))->toBeFalse();
    expect(authorCarouselViewCount($author, $story))->toBe(0);
    expect($story->fresh()->view_count)->toBe(0);
});

it('does not create a partial index entry for an unindexed story', function () {
    $author = Profile::factory()->create(['user_id' => null]);
    $viewer = Profile::factory()->create(['user_id' => null]);
    $story = makeViewCountStory($author);

    expect(app(StoryViewService::class)->record($story, (int) $viewer->id))->toBeTrue();

    expect((bool) Redis::exists("story:{$story->id}"))->toBeFalse();
    expect($story->fresh()->view_count)->toBe(1);
});
