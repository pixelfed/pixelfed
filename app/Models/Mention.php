<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $status_id
 * @property int $profile_id
 * @property int $local
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Profile|null $profile
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mention withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Mention extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }
}
