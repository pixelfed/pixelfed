<?php

use App\Models\Like;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v1/favourites pagination
|--------------------------------------------------------------------------
|
| The likes page query uses a strict `<` bound on likes.id, so the rel="next"
| cursor must be exactly the smallest like_id on the page. Subtracting 1 skips
| the favourite at (min - 1), so traversing the Link header can drop a row.
|
*/

/**
 * Create a public photo status owned by $author and have $liker favourite it.
 * Returns the created Like.
 */
function favourite(User $author, User $liker): Like
{
    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
    ]);

    return Like::create([
        'profile_id' => $liker->profile_id,
        'status_id' => $status->id,
    ]);
}

/**
 * Parse the max_id value out of a rel="next" Link header.
 */
function nextMaxId(?string $linkHeader): ?int
{
    if (! $linkHeader) {
        return null;
    }

    if (preg_match('/max_id=(\d+)>; rel="next"/', $linkHeader, $m)) {
        return (int) $m[1];
    }

    return null;
}

it('traverses both pages returning every favourite with no skipped rows', function () {
    $author = User::factory()->create();
    $author->refresh();
    $user = User::factory()->create();
    $user->refresh();

    // Five favourites; capture their like_ids (autoincrement, ascending).
    $likeIds = collect(range(1, 5))
        ->map(fn () => favourite($author, $user)->id)
        ->sort()
        ->values()
        ->all();

    Passport::actingAs($user, ['read']);

    // Page 1: newest 3 by like_id desc.
    $page1 = $this->getJson('/api/v1/favourites?limit=3')->assertOk();
    $page1Ids = collect($page1->json())->pluck('like_id')->all();

    expect($page1Ids)->toHaveCount(3);

    $nextMaxId = nextMaxId($page1->headers->get('Link'));
    expect($nextMaxId)->not->toBeNull();

    // Page 2: follow rel="next".
    $page2 = $this->getJson("/api/v1/favourites?limit=3&max_id={$nextMaxId}")->assertOk();
    $page2Ids = collect($page2->json())->pluck('like_id')->all();

    // The union of both pages must cover every favourite with no gap.
    $seen = collect($page1Ids)->merge($page2Ids)->unique()->sort()->values()->all();

    expect($seen)->toBe($likeIds);
});
