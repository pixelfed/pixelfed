<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StoryController::recent deterministic per-author selection
|--------------------------------------------------------------------------
|
| recent() returns one card per followed author. It used to pick that row
| indeterminately (groupBy(stories.*) with post-group ordering, or unique()
| without ordering), so an author with two active stories could show either
| one's preview. The card must reflect the author's newest active story
| (MAX(id)), matching StoryService::latest().
|
*/

beforeEach(function () {
    config(['instance.stories.enabled' => true, 'image.driver' => 'gd']);
    Cache::flush();
});

function makeActiveStory(Profile $author, int $durationHours = 4): Story
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
    $story->expires_at = now()->addHours($durationHours);
    $story->save();

    return $story;
}

function followedAuthorWithStories(User $viewer, int $count): array
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->id,
        'local_profile' => true,
    ]);

    $stories = [];
    foreach (range(1, $count) as $i) {
        $stories[] = makeActiveStory($author, 4 + $i);
    }

    return [$author, $stories];
}

it('shows the newest active story for a followed author with two active stories', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    [$author, $stories] = followedAuthorWithStories($viewer, 2);

    // "Newest" is the max id, matching StoryService::latest(); snowflake ids
    // are not guaranteed to increase with creation order within a tick.
    $newestId = collect($stories)->max(fn ($s) => (int) $s->id);

    $row = collect($this->actingAs($viewer, 'web')
        ->getJson('/api/web/stories/v1/recent')
        ->assertOk()
        ->json())
        ->firstWhere('pid', $author->id);

    expect($row)->not->toBeNull()
        ->and((int) $row['latest']['id'])->toBe($newestId)
        ->and((int) $row['sid'])->toBe($newestId);
});

it('returns exactly one card per followed author', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    [$author] = followedAuthorWithStories($viewer, 3);

    $rows = collect($this->actingAs($viewer, 'web')
        ->getJson('/api/web/stories/v1/recent')
        ->assertOk()
        ->json())
        ->where('pid', $author->id);

    expect($rows)->toHaveCount(1);
});
