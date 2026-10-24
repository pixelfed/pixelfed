<?php

namespace Tests\Unit;

use App\Http\Controllers\OAuth\OobAuthorizationController;
use Laravel\Passport\Bridge\Client;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * isOutOfBandRequest() must detect an OOB client even when redirect_uri is
 * omitted on the authorize request (RFC 6749 §3.1.2.3), in which case the
 * league server leaves the auth request's redirect URI null.
 */
class OobAuthorizationTest extends TestCase
{
    private function isOob(AuthorizationRequest $req): bool
    {
        $controller = app(OobAuthorizationController::class);
        $m = new ReflectionMethod($controller, 'isOutOfBandRequest');
        $m->setAccessible(true);

        return $m->invoke($controller, $req);
    }

    private function authRequest(?string $redirectUri, array $registered): AuthorizationRequest
    {
        $client = new Client('cid', 'Test App', $registered);
        $req = new AuthorizationRequest;
        $req->setClient($client);
        $req->setRedirectUri($redirectUri);

        return $req;
    }

    #[Test]
    public function it_detects_oob_when_redirect_uri_is_explicitly_set(): void
    {
        $req = $this->authRequest('urn:ietf:wg:oauth:2.0:oob', ['urn:ietf:wg:oauth:2.0:oob']);

        $this->assertTrue($this->isOob($req));
    }

    #[Test]
    public function it_detects_oob_when_redirect_uri_is_omitted_for_oob_only_client(): void
    {
        // redirect_uri omitted -> null on the auth request; client registered
        // only the OOB URI.
        $req = $this->authRequest(null, ['urn:ietf:wg:oauth:2.0:oob']);

        $this->assertTrue($this->isOob($req));
    }

    #[Test]
    public function it_does_not_treat_a_normal_http_client_as_oob(): void
    {
        $req = $this->authRequest('https://app.example/callback', ['https://app.example/callback']);

        $this->assertFalse($this->isOob($req));
    }

    #[Test]
    public function it_does_not_treat_an_omitted_redirect_for_a_multi_uri_client_as_oob(): void
    {
        $req = $this->authRequest(null, ['urn:ietf:wg:oauth:2.0:oob', 'https://app.example/callback']);

        $this->assertFalse($this->isOob($req));
    }
}
