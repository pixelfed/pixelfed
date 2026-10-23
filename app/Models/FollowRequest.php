<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $follower_id
 * @property int $following_id
 * @property array<array-key, mixed>|null $activity
 * @property int $is_rejected
 * @property int $is_local
 * @property string|null $handled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $actor
 * @property-read Profile|null $follower
 * @property-read Profile|null $following
 * @property-read Profile|null $target
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereFollowerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereFollowingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereHandledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereIsLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereIsRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FollowRequest whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FollowRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'activity' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function follower()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function following()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function permalink($append = null, $namespace = '#accepts')
    {
        $path = $this->target->permalink("{$namespace}/follows/{$this->id}{$append}");

        return url($path);
    }
}
