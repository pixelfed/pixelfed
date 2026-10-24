<?php

namespace App\Auth;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

final class BearerTokenResponse extends \League\OAuth2\Server\ResponseTypes\BearerTokenResponse
{
    /**
     * Add custom fields to your Bearer Token response here, then override
     * AuthorizationServer::getResponseType() to pull in your version of
     * this class rather than the default.
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return [
            'scope' => implode(' ', array_map(fn ($scope) => $scope->getIdentifier(), $accessToken->getScopes())),
            'created_at' => time(),
        ];
    }
}
