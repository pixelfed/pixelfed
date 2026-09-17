<?php

namespace App\Auth;

use App\Models\User;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mirrors Laravel\Passport\PersonalAccessTokenFactory: a dedicated
 * AuthorizationServer instance (not the shared singleton behind
 * /oauth/token) with only AppRegisterGrant enabled. Tokens it issues are
 * indistinguishable from ones issued by the authorization code flow, so
 * the standard refresh_token grant works on them.
 */
class AppRegisterTokenFactory
{
    protected ?AuthorizationServer $server = null;

    /**
     * Returns the decoded /oauth/token style payload:
     * token_type, expires_in, access_token, refresh_token.
     *
     * @param  string[]  $scopes
     * @return array<string, mixed>
     *
     * @throws OAuthServerException
     */
    public function issue(
        User $user,
        string $clientId,
        string $clientSecret,
        array $scopes,
        ?string $name = null
    ): array {
        $response = $this->server()->respondToAccessTokenRequest(
            $this->createRequest($user, $clientId, $clientSecret, $scopes, $name),
            app(ResponseInterface::class)
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Cheap pre-check so the controller can reject bad client credentials
     * before it creates the user. Same repository call the grant makes.
     */
    public function validateClient(string $clientId, string $clientSecret): bool
    {
        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        return app(ClientRepository::class)->validateClient(
            $clientId,
            $clientSecret,
            AppRegisterGrant::IDENTIFIER
        );
    }

    /**
     * @param  string[]  $scopes
     */
    protected function createRequest(
        User $user,
        string $clientId,
        string $clientSecret,
        array $scopes,
        ?string $name
    ): ServerRequestInterface {
        return (new PsrHttpFactory)->createRequest(Request::create(config('app.url'), 'POST', [
            'grant_type' => AppRegisterGrant::IDENTIFIER,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'user_id' => (string) $user->getKey(),
            'scope' => implode(' ', $scopes),
            'name' => $name,
        ]));
    }

    protected function server(): AuthorizationServer
    {
        if ($this->server instanceof AuthorizationServer) {
            return $this->server;
        }

        $server = new AuthorizationServer(
            app(ClientRepository::class),
            app(AccessTokenRepository::class),
            app(ScopeRepository::class),
            $this->privateKey(),
            Passport::tokenEncryptionKey(app('encrypter')),
            new BearerTokenResponse
        );

        $server->setDefaultScope(Passport::$defaultScope);
        $server->revokeRefreshTokens(Passport::$revokeRefreshTokenAfterUse);

        $grant = new AppRegisterGrant(app(RefreshTokenRepository::class));
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        $server->enableGrantType($grant, Passport::tokensExpireIn());

        return $this->server = $server;
    }

    /**
     * Same resolution as PassportServiceProvider::makeCryptKey('private').
     */
    protected function privateKey(): CryptKey
    {
        $key = str_replace('\\n', "\n", config('passport.private_key') ?? '');

        if (! $key) {
            $key = 'file://'.Passport::keyPath('oauth-private.key');
        }

        return new CryptKey($key, null, Passport::$validateKeyPermissions);
    }
}
