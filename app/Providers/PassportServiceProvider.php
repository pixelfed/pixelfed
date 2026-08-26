<?php

namespace App\Providers;

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

        $this->app->singleton(ClientRepository::class, CachedPersonalAccessClientRepository::class);
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
