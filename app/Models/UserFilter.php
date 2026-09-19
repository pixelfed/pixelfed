<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $filterable_id
 * @property string $filterable_type
 * @property string $filter_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Instance|null $instance
 * @property-read \App\Models\Profile|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereFilterType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereFilterableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereFilterableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserFilter whereUserId($value)
 * @mixin \Eloquent
 */
class UserFilter extends Model
{
    protected $guarded = [];

    public function mutedUserIds($profile_id)
    {
        return $this->whereUserId($profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->pluck('filterable_id');
    }

    public function blockedUserIds($profile_id)
    {
        return $this->whereUserId($profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->pluck('filterable_id');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class, 'filterable_id');
    }

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id');
    }
}
