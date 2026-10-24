<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token as PassportToken;

/**
 * @property string $id
 * @property int|null $user_id
 * @property int $client_id
 * @property string|null $name
 * @property array<array-key, mixed>|null $scopes
 * @property bool $revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $expires_at
 * @property-read Client|null $client
 * @property-read RefreshToken|null $refreshToken
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken existsIn(array $haystack)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereRevoked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereScopes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OAuthToken whereUserId($value)
 *
 * @mixin \Eloquent
 */
class OAuthToken extends PassportToken
{
    protected $visible = [
        'id',
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'created_at',
        'updated_at',
        'expires_at',
    ];
}
