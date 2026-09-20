<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $software
 * @property string|null $domain
 * @property string|null $webfinger
 * @property int|null $instance_id
 * @property int|null $user_id
 * @property int|null $client_id
 * @property string|null $ip_address
 * @property string|null $bearer_token
 * @property array<array-key, mixed>|null $verify_credentials
 * @property Carbon|null $last_successful_login_at
 * @property Carbon|null $last_verify_credentials_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereBearerToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereLastSuccessfulLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereLastVerifyCredentialsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereSoftware($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereVerifyCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuth whereWebfinger($value)
 *
 * @mixin \Eloquent
 */
class RemoteAuth extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verify_credentials' => 'array',
            'last_successful_login_at' => 'datetime',
            'last_verify_credentials_at' => 'datetime',
        ];
    }
}
