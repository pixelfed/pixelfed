<?php

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story publishNext() overlay validation
|--------------------------------------------------------------------------
|
| publishNext() throws ValidationException::withMessages() for a bad
| overlay (invalid hashtag/url/mention/text), but the broad
| `catch (\Exception $e)` around it caught ValidationException too
| (it extends \Exception), turning the intended 422 with field errors
| into a generic 500 "Failed to create story" and logging ordinary user
| input as a server error.
|
*/

beforeEach(function () {
    Storage::fake('local');
    config(['instance.stories.enabled' => true]);
});

it('returns a 422 with the field error instead of a generic 500 for an invalid overlay', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    $response = $this->postJson('/api/v1.2/stories/publish', [
        'image' => UploadedFile::fake()->image('story.jpg', 1080, 1920),
        'overlays' => [
            ['type' => 'hashtag', 'content' => 'not a valid hashtag'],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['overlays.0.content']);
    expect($response->json('msg'))->not->toBe('Failed to create story');

    expect(Story::whereProfileId($user->profile_id)->count())->toBe(0);
});

it('does not leave an open transaction after rejecting an invalid overlay', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    $levelBefore = DB::transactionLevel();

    $this->postJson('/api/v1.2/stories/publish', [
        'image' => UploadedFile::fake()->image('story.jpg', 1080, 1920),
        'overlays' => [
            ['type' => 'hashtag', 'content' => 'not a valid hashtag'],
        ],
    ])->assertStatus(422);

    expect(DB::transactionLevel())->toBe($levelBefore);
});
