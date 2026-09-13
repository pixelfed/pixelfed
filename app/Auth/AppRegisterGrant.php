<?php

namespace App\Auth;

use DateInterval;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Issues an access token + refresh token pair for a given user against a
 * client the app registered through /api/v1/apps. Only ever enabled on the
 * private authorization server built by AppRegisterTokenFactory, so it is
 * never reachable through /oauth/token.
 */
class AppRegisterGrant extends AbstractGrant
{
    public const IDENTIFIER = 'app_register';

    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $client = $this->validateRegisteredClient($request);

        $userIdentifier = $this->getRequestParameter('user_id', $request);

        if ($userIdentifier === null || $userIdentifier === '') {
            throw OAuthServerException::invalidRequest('user_id');
        }

        $userIdentifier = (string) $userIdentifier;

        $scopes = $this->scopeRepository->finalizeScopes(
            $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope)),
            $this->getIdentifier(),
            $client,
            $userIdentifier
        );

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);

        $this->getEmitter()->emit(
            new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken)
        );

        Passport::token()->newQuery()->whereKey($accessToken->getIdentifier())->update([
            'name' => $this->getRequestParameter('name', $request) ?: $client->getName(),
        ]);

        $responseType->setAccessToken($accessToken);

        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(
                new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken)
            );
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }

    /**
     * Same checks as AbstractGrant::validateClient minus the grant_types
     * gate. Clients created by /api/v1/apps have no explicit grant_types
     * column, so Passport computes the list and "app_register" is never in
     * it. We only need: client exists, is confidential, secret matches.
     */
    protected function validateRegisteredClient(ServerRequestInterface $request): ClientEntityInterface
    {
        [$clientId, $clientSecret] = $this->getClientCredentials($request);

        $client = $this->clientRepository->getClientEntity($clientId);

        if (! $client instanceof ClientEntityInterface || ! $client->isConfidential()) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }

        if (
            $clientSecret === '' ||
            ! $this->clientRepository->validateClient($clientId, $clientSecret, $this->getIdentifier())
        ) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }

        return $client;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
