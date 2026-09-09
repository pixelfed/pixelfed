<?php

use App\Http\Controllers\SearchController;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SearchController case-insensitive operator
|--------------------------------------------------------------------------
|
| Search must use ILIKE on PostgreSQL (whose LIKE is case-sensitive) so
| case-mismatched queries still match, mirroring MySQL/SQLite behaviour.
|
*/

function likeOperatorFor(string $driver): string
{
    config(['database.default' => $driver]);
    $controller = app(SearchController::class);
    $m = new ReflectionMethod($controller, 'likeOperator');
    $m->setAccessible(true);

    return $m->invoke($controller);
}

it('uses ILIKE on postgres', function () {
    expect(likeOperatorFor('pgsql'))->toBe('ilike');
});

it('uses LIKE on mysql', function () {
    expect(likeOperatorFor('mysql'))->toBe('like');
});

it('uses LIKE on sqlite', function () {
    expect(likeOperatorFor('sqlite'))->toBe('like');
});

it('search endpoint still responds on the default driver', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->getJson('/api/search?'.http_build_query([
            'q' => 'photography',
            'src' => 'metro',
            'v' => 2,
            'scope' => 'hashtag',
        ]))
        ->assertOk();
});
