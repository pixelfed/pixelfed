<?php

namespace Tests\Unit;

use App\Http\Middleware\RestrictedAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RestrictedAccessMiddlewareTest extends TestCase
{
    protected RestrictedAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RestrictedAccess;
    }

    protected function passThrough(): \Closure
    {
        return function ($request) {
            return new Response('OK', 200);
        };
    }

    #[Test]
    public function it_allows_all_requests_when_restricted_mode_is_disabled()
    {
        Config::set('instance.restricted.enabled', false);
        Auth::shouldReceive('guard')->never();

        $request = Request::create('/discover', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_redirects_unauthenticated_users_to_login_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/discover', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    #[Test]
    public function it_allows_authenticated_users_through_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $this->actingAs(\App\User::factory()->make());

        $request = Request::create('/discover', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_login_route_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/login', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_password_reset_routes_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/password/reset', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_oauth_token_route_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/oauth/token', 'POST');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_well_known_routes_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/.well-known/webfinger', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_oidc_routes_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/auth/oidc/start', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_register_when_open_registration_enabled()
    {
        Config::set('instance.restricted.enabled', true);
        Config::set('pixelfed.open_registration', true);

        $request = Request::create('/register', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_blocks_register_when_open_registration_disabled()
    {
        Config::set('instance.restricted.enabled', true);
        Config::set('pixelfed.open_registration', false);

        $request = Request::create('/register', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    #[Test]
    public function it_blocks_profile_pages_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/someuser', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    #[Test]
    public function it_allows_curated_sign_up_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/auth/sign_up', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_api_v1_apps_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/api/v1/apps', 'POST');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_api_v1_instance_when_restricted()
    {
        Config::set('instance.restricted.enabled', true);

        $request = Request::create('/api/v1/instance', 'GET');
        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }
}
