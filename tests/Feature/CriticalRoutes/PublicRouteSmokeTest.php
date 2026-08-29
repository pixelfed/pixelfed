<?php

use App\Models\Page;
use App\Models\Status;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Public Route Smoke Tests
|--------------------------------------------------------------------------
|
| These tests verify that publicly accessible routes return expected
| HTTP status codes without authentication. They catch routing errors,
| missing controllers, and broken view rendering.
|
*/

test('login page loads', function () {
    $this->get('/login')
        ->assertStatus(200);
});

test('well-known nodeinfo returns json', function () {
    $this->getJson('/.well-known/nodeinfo')
        ->assertStatus(200)
        ->assertJsonStructure(['links']);
});

test('webfinger returns 400 without resource param', function () {
    $this->getJson('/.well-known/webfinger')
        ->assertStatus(400);
});

test('discover page respects public config', function () {
    config(['instance.discover.public' => true]);

    $this->get('/discover')
        ->assertStatus(200);
});

describe('routes that require database', function () {
    uses(RefreshDatabase::class);

    test('register page loads', function () {
        $this->get('/register')
            ->assertStatus(200);
    });

    test('nodeinfo endpoint returns json', function () {
        $this->getJson('/api/nodeinfo/2.0.json')
            ->assertStatus(200)
            ->assertJsonStructure(['version', 'software', 'usage']);
    });

    test('api v1 instance returns json', function () {
        $this->getJson('/api/v1/instance')
            ->assertStatus(200)
            ->assertJsonStructure(['uri', 'title', 'description']);
    });

    test('homepage returns 200 for unauthenticated user', function () {
        $this->get('/')
            ->assertStatus(200);
    });

    test('api v1 apps endpoint accepts POST', function () {
        $this->postJson('/api/v1/apps', [
            'client_name' => 'Test App',
            'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
            'scopes' => 'read',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['client_id', 'client_secret']);
    });
});

/*
|--------------------------------------------------------------------------
| Cached-model regression tests
|--------------------------------------------------------------------------
|
| These guard against a class of bug where an Eloquent model was cached
| directly (e.g. Cache::remember(..., fn () => $model)). On a cache read the
| model could deserialize into a __PHP_Incomplete_Class, throwing
| "attempt to access a property on an incomplete object" and returning a 500.
|
| The failure only surfaced on the SECOND request (the cache-read path), so
| every test here hits the route twice.
|
*/

describe('guest profile page (regression: cached UserSetting model)', function () {
    uses(RefreshDatabase::class);

    it('renders a public profile for an unauthenticated user', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->get('/'.$user->username)
            ->assertStatus(200);
    });

    it('renders a public profile on a repeated request (cache-read path)', function () {
        $user = User::factory()->create();
        $user->refresh();

        // First request populates the profile:settings cache.
        $this->get('/'.$user->username)
            ->assertStatus(200);

        // Second request reads from cache — this is where a cached Eloquent
        // model previously deserialized as an incomplete object and 500'd.
        $this->get('/'.$user->username)
            ->assertStatus(200);
    });

    it('renders a public profile when the user has no settings row', function () {
        $user = User::factory()->create();
        $user->refresh();

        // Simulate a missing user_settings row; the controller must fall back
        // to defaults instead of throwing on a null relation.
        UserSetting::where('user_id', $user->id)->delete();

        $this->get('/'.$user->username)->assertStatus(200);
        $this->get('/'.$user->username)->assertStatus(200);
    });
});

describe('static site pages (regression: cached Page model)', function () {
    uses(RefreshDatabase::class);

    it('loads terms of use twice', function () {
        $this->get('/site/terms')->assertStatus(200);
        $this->get('/site/terms')->assertStatus(200);
    });

    it('loads privacy policy twice', function () {
        $this->get('/site/privacy')->assertStatus(200);
        $this->get('/site/privacy')->assertStatus(200);
    });

    it('renders db-backed terms content twice', function () {
        Page::create([
            'slug' => '/site/terms',
            'title' => 'Terms',
            'content' => '<p>Custom terms content</p>',
            'active' => true,
        ]);

        $this->get('/site/terms')
            ->assertStatus(200)
            ->assertSee('Custom terms content', false);

        $this->get('/site/terms')
            ->assertStatus(200)
            ->assertSee('Custom terms content', false);
    });

    it('loads legal notice twice when a page exists', function () {
        Page::create([
            'slug' => '/site/legal-notice',
            'title' => 'Legal Notice',
            'content' => '<p>Legal notice body</p>',
            'active' => true,
        ]);

        $this->get('/site/legal-notice')
            ->assertStatus(200)
            ->assertSee('Legal notice body', false);

        $this->get('/site/legal-notice')
            ->assertStatus(200)
            ->assertSee('Legal notice body', false);
    });

    it('loads mobile terms and privacy twice', function () {
        $this->get('/e/terms')->assertStatus(200);
        $this->get('/e/terms')->assertStatus(200);
        $this->get('/e/privacy')->assertStatus(200);
        $this->get('/e/privacy')->assertStatus(200);
    });
});

describe('static informational pages load for guests', function () {
    test('help index loads', function () {
        $this->get('/site/help')->assertStatus(200);
    });

    test('fediverse info page loads', function () {
        $this->get('/site/fediverse')->assertStatus(200);
    });

    test('open source page loads', function () {
        $this->get('/site/open-source')->assertStatus(200);
    });

    test('developer api page loads', function () {
        $this->get('/site/developer-api')->assertStatus(200);
    });

    test('getting started kb page loads', function () {
        $this->get('/site/kb/getting-started')->assertStatus(200);
    });

    test('what is the fediverse kb page loads', function () {
        $this->get('/site/kb/what-is-the-fediverse')->assertStatus(200);
    });
});

describe('community guidelines page (regression: cached page render)', function () {
    uses(RefreshDatabase::class);

    it('loads the fallback twice', function () {
        // No Page row exists; the route caches the rendered view either way.
        $this->get('/site/kb/community-guidelines')->assertStatus(200);
        $this->get('/site/kb/community-guidelines')->assertStatus(200);
    });

    it('renders db-backed content twice', function () {
        Page::create([
            'slug' => '/site/kb/community-guidelines',
            'title' => 'Community Guidelines',
            'content' => '<p>Be excellent to each other</p>',
            'active' => true,
        ]);

        $this->get('/site/kb/community-guidelines')
            ->assertStatus(200)
            ->assertSee('Be excellent to each other', false);

        $this->get('/site/kb/community-guidelines')
            ->assertStatus(200)
            ->assertSee('Be excellent to each other', false);
    });
});

describe('public status page (regression: cached status/media services)', function () {
    uses(RefreshDatabase::class);

    it('renders a public post for a guest on repeated requests', function () {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'uri' => null,
        ]);

        $url = "/p/{$user->username}/{$status->id}";

        // Two requests: the second exercises the cache-read path in
        // StatusService/MediaService.
        $this->get($url)->assertStatus(200);
        $this->get($url)->assertStatus(200);
    });
});

describe('profile activitypub object (regression: cached AP object)', function () {
    uses(RefreshDatabase::class);

    beforeEach(function () {
        // config_cache() falls back to config() when the DB-backed config
        // cache is disabled, so setting these makes the test deterministic.
        config([
            'instance.enable_cc' => false,
            'federation.activitypub.enabled' => true,
        ]);
    });

    it('returns activitypub json for a guest on repeated requests', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->getJson('/users/'.$user->username, [
            'Accept' => 'application/activity+json',
        ])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/activity+json');

        // Second request reads the cached AP object.
        $this->getJson('/users/'.$user->username, [
            'Accept' => 'application/activity+json',
        ])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/activity+json');
    });
});
