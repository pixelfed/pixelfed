<?php

namespace App\Providers;

use App\Models\OAuthToken;
use App\Passport\CachedPersonalAccessClientRepository;
use Laravel\Passport\Bridge;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

class PassportServiceProvider extends \Laravel\Passport\PassportServiceProvider
{
    public function register(): void
    {
        parent::register();

        Passport::ignoreRoutes();

        $this->app->singleton(ClientRepository::class, CachedPersonalAccessClientRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Passport::$clientUuids = false;
        Passport::authorizationView('auth.oauth.authorize');

        Passport::useTokenModel(OAuthToken::class);
        Passport::tokensExpireIn(now()->addDays(config('instance.oauth.token_expiration', 356)));
        Passport::refreshTokensExpireIn(now()->addDays(config('instance.oauth.refresh_expiration', 400)));
        Passport::enableImplicitGrant();

        if (config('instance.oauth.pat.enabled')) {
            Passport::personalAccessClientId(config('instance.oauth.pat.id'));
        }

        Passport::tokensCan([
            'read' => 'Full read access to your account',
            'write' => 'Full write access to your account',
            'follow' => 'Ability to follow other profiles',
            'admin:read' => 'Read all data on the server',
            'admin:read:domain_blocks' => 'Read sensitive information of all domain blocks',
            'admin:write' => 'Modify all data on the server',
            'admin:write:domain_blocks' => 'Perform moderation actions on domain blocks',
            'push' => 'Receive your push notifications',
        ]);

        Passport::setDefaultScope([
            'read',
            'write',
            'follow',
        ]);
    }

    /**
     * Make the authorization service instance.
     */
    public function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): AuthorizationServer
    {
        return tap(new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            $this->makeCryptKey('private'),
            Passport::tokenEncryptionKey($this->app->make('encrypter')),
            $responseType ?? Passport::$authorizationServerResponseType
        ), function (AuthorizationServer $server): void {
            $server->setDefaultScope(Passport::$defaultScope);
            $server->revokeRefreshTokens(Passport::$revokeRefreshTokenAfterUse);
        });
    }
}
