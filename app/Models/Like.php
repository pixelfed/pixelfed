<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $status_id
 * @property int|null $status_profile_id
 * @property int|null $is_comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Profile|null $actor
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereIsComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereStatusProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Like extends Model
{
    use SoftDeletes;

    const MAX_PER_DAY = 1500;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
