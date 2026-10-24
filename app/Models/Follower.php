<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $following_id
 * @property int $local_profile
 * @property int $local_following
 * @property int $show_reblogs
 * @property int $notify
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $actor
 * @property-read Profile|null $profile
 * @property-read Profile|null $target
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereFollowingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereLocalFollowing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereLocalProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereNotify($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereShowReblogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Follower whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Follower extends Model
{
    protected $guarded = [];

    const MAX_FOLLOWING = 7500;

    const FOLLOW_PER_HOUR = 150;

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function permalink($append = null)
    {
        $path = $this->actor->permalink("#accepts/follows/{$this->id}{$append}");

        return url($path);
    }
}
