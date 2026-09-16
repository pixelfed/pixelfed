<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Router;
use Laravel\Passport\Passport;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account API Tests
|--------------------------------------------------------------------------
*/

describe('GET /api/v1/accounts/verify_credentials', function () {
    it('returns the authenticated users account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/verify_credentials')
            ->assertOk()
            ->assertJsonStructure(['id', 'username', 'acct', 'display_name', 'note'])
            ->assertJsonFragment(['username' => $user->username]);
    });
});

describe('GET /api/v1/accounts/{id}', function () {
    it('returns a public account by id', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$targetUser->profile_id}")
            ->assertOk()
            ->assertJsonFragment(['username' => $targetUser->username]);
    });

    it('returns 404 for a non-existent account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/999999999')
            ->assertNotFound();
    });
});

describe('GET /api/v1/accounts/{id}/statuses', function () {
    it('returns statuses for a public account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Status::factory()->count(3)->create([
            'profile_id' => $targetUser->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses")
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('does not duplicate the boundary status across max_id pages', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Status::factory()->count(25)->create([
            'profile_id' => $targetUser->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);
        Passport::actingAs($user, ['read']);

        $pageOneIds = collect(
            $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses?limit=20")
                ->assertOk()
                ->json()
        )->pluck('id')->all();

        $lastId = end($pageOneIds);

        $pageTwoIds = collect(
            $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses?limit=20&max_id={$lastId}")
                ->assertOk()
                ->json()
        )->pluck('id')->all();

        expect($pageTwoIds[0] ?? null)->not->toBe($lastId)
            ->and(array_intersect($pageOneIds, $pageTwoIds))->toBeEmpty();
    });

    it('accepts limit=100 as requested by the Portfolio Curate page', function () {
        // The Portfolio "Curate" page requests limit=100. A strict max:40
        // validation rule 422'd that request and broke the page (#7328); the
        // endpoint now allows up to 100.
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Status::factory()->count(45)->create([
            'profile_id' => $targetUser->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);
        Passport::actingAs($user, ['read']);

        $body = $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses?only_media=1&limit=100")
            ->assertOk()
            ->assertJsonIsArray()
            ->json();

        // All 45 media statuses fit under the raised cap of 100.
        expect(count($body))->toBe(45);
    });

    it('rejects a limit above the 100 maximum', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses?limit=101")
            ->assertStatus(422);
    });
});

describe('GET /api/v1/accounts/{id}/followers', function () {
    it('returns followers for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/followers")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('GET /api/v1/accounts/{id}/following', function () {
    it('returns following list for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/following")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('GET /api/v1/accounts/relationships', function () {
    it('returns relationship info for given accounts', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/relationships?id[]={$other->profile_id}")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('scope enforcement on writes', function () {
    it('rejects a token without write scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read']);

        $this->postJson("/api/v1/statuses/{$status->id}/favourite")
            ->assertForbidden();
    });

    it('allows a token with write scope', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read', 'write']);

        $this->postJson("/api/v1/statuses/{$status->id}/favourite")
            ->assertOk();
    });
});

describe('first-party session auth', function () {
    it('allows writes without a token', function () {
        // Derive the stateful domain from app.url so the Origin header and the
        // sanctum.stateful entry always match, regardless of the environment's
        // configured APP_URL (e.g. pixelfed.test in CI vs a local dev domain).
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        config(['sanctum.stateful' => [$host]]);
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);

        $this->actingAs($user)
            ->withHeader('Origin', config('app.url'))
            ->postJson("/api/v1/statuses/{$status->id}/favourite")
            ->assertOk();
    });
});

it('applies stateful middleware to the api group', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($r) => $r->uri() === 'api/v1/accounts/verify_credentials');

    $resolved = app(Router::class)
        ->gatherRouteMiddleware($route);

    expect($resolved)
        ->toContain(EnsureFrontendRequestsAreStateful::class);
});
