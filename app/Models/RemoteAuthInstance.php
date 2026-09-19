<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $domain
 * @property int|null $instance_id
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $redirect_uri
 * @property string|null $root_domain
 * @property int|null $allowed
 * @property int $banned
 * @property int $active
 * @property string|null $last_refreshed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereAllowed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereLastRefreshedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereRedirectUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereRootDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RemoteAuthInstance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RemoteAuthInstance extends Model
{
    use HasFactory;

    protected $guarded = [];
}
