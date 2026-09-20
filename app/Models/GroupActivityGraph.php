<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $instance_id
 * @property int|null $actor_id
 * @property string|null $verb
 * @property string|null $id_url
 * @property string|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereIdUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupActivityGraph whereVerb($value)
 *
 * @mixin \Eloquent
 */
class GroupActivityGraph extends Model
{
    use HasFactory;
}
