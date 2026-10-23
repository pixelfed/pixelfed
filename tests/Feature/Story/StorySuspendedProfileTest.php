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
| Stories must respect suspended/deleted profile status
|--------------------------------------------------------------------------
|
| Suspended/deleted profiles (profiles.status != null) are unavailable to
| everyone, including the account itself, for posts and other content. The
| story endpoints skipped that gate: StoryController::profile() loaded via
| findOrFail with no status check, StoryController::recent() joined without
| a profiles.status filter, and ProfileController::stories() only filtered
| domain. All three must exclude suspended profiles.
|
*/

beforeEach(function () {
    config(['instance.stories.enabled' => true, 'image.driver' => 'gd']);
    Cache::flush();
});

function makeSuspendedStory(Profile $author): Story
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
    $story->expires_at = now()->addHours(12);
    $story->save();

    return $story;
}

function followProfile(User $viewer, Profile $author): void
{
    Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->id,
        'local_profile' => true,
    ]);
}

it('returns 404 from the story profile api for a suspended author (follower view)', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();
    $authorProfile = $author->profile;
    followProfile($viewer, $authorProfile);
    makeSuspendedStory($authorProfile);

    // Sanity: an active author's stories are reachable.
    $this->actingAs($viewer, 'web')
        ->getJson('/api/web/stories/v1/profile/'.$authorProfile->id)
        ->assertOk();

    // Suspend the author. 'disabled' (not 'delete') isolates the story
    // status gate: AccountService::get() still resolves a disabled account,
    // so only the added status check can produce the 404 here.
    $authorProfile->status = 'disabled';
    $authorProfile->save();

    $this->actingAs($viewer, 'web')
        ->getJson('/api/web/stories/v1/profile/'.$authorProfile->id)
        ->assertNotFound();
});

it('returns 404 when a suspended user views their own stories', function () {
    $author = User::factory()->create();
    $author->refresh();
    $authorProfile = $author->profile;
    makeSuspendedStory($authorProfile);

    $authorProfile->status = 'disabled';
    $authorProfile->save();

    $this->actingAs($author, 'web')
        ->getJson('/api/web/stories/v1/profile/'.$authorProfile->id)
        ->assertNotFound();
});

it('omits a suspended author from the recent stories feed', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $active = User::factory()->create();
    $active->refresh();
    $suspended = User::factory()->create();
    $suspended->refresh();

    followProfile($viewer, $active->profile);
    followProfile($viewer, $suspended->profile);

    makeSuspendedStory($active->profile);
    makeSuspendedStory($suspended->profile);

    $suspended->profile->status = 'delete';
    $suspended->profile->save();

    $pids = collect($this->actingAs($viewer, 'web')
        ->getJson('/api/web/stories/v1/recent')
        ->assertOk()
        ->json())
        ->pluck('pid')
        ->map(fn ($id) => (string) $id);

    expect($pids)->toContain((string) $active->profile->id);
    expect($pids)->not->toContain((string) $suspended->profile->id);
});

it('returns 404 from the profile stories view for a suspended profile', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();
    $authorProfile = $author->profile;
    followProfile($viewer, $authorProfile);
    makeSuspendedStory($authorProfile);

    $authorProfile->status = 'delete';
    $authorProfile->save();

    $this->actingAs($viewer, 'web')
        ->get('/stories/'.$authorProfile->username)
        ->assertNotFound();
});
