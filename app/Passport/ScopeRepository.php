<?php

namespace App\Passport;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Bridge\ScopeRepository as BaseScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class ScopeRepository extends BaseScopeRepository
{
    /**
     * Scopes that only an admin account can be issued.
     */
    public static function isAdminScope(string $scope): bool
    {
        return Str::startsWith($scope, 'admin:');
    }

    /**
     * Called by the OAuth server at token issuance for every grant type.
     * The parent handles the usual checks (known scope, client allowed to
     * request it, no "*" on the auth code flow); this layer additionally
     * drops admin:* unless the token is being issued to an admin user.
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        string|int|null $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        $scopes = parent::finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);

        $user = $userIdentifier !== null ? User::find($userIdentifier) : null;

        if ($user && $user->is_admin) {
            return $scopes;
        }

        // No user (client_credentials) or a non-admin user: never issue admin scopes.
        return collect($scopes)
            ->reject(fn ($scope): bool => self::isAdminScope($scope->getIdentifier()))
            ->values()
            ->all();
    }
}
