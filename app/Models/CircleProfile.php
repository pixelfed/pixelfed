<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $owner_id
 * @property int $circle_id
 * @property int $profile_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereCircleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CircleProfile whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CircleProfile extends Model
{
    protected $guarded = [];
}
