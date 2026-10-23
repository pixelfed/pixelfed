<?php

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v1/followed_tags Link header direction
|--------------------------------------------------------------------------
|
| The Link header variables were inverted, so the first (cursor-less) page
| emitted only rel="prev" and no rel="next", breaking forward pagination for
| Mastodon clients. nextPageUrl() must be labeled rel="next".
|
*/

it('emits rel="next" on the first followed-tags page', function () {
    $user = User::factory()->create();
    $user->refresh();

    foreach (['alpha', 'bravo', 'charlie'] as $name) {
        $tag = Hashtag::create(['name' => $name, 'slug' => $name]);
        HashtagFollow::create([
            'user_id' => $user->id,
            'profile_id' => $user->profile_id,
            'hashtag_id' => $tag->id,
        ]);
    }

    Passport::actingAs($user, ['read']);

    $response = $this->getJson('/api/v1/followed_tags?limit=1');
    $response->assertOk();

    $link = $response->headers->get('Link');

    // Forward pagination must be reachable from the entry point.
    expect($link)->not->toBeNull();
    expect($link)->toContain('rel="next"');
    // The first page has no cursor, so there is no previous (newer) page.
    expect($link)->not->toContain('rel="prev"');
});
