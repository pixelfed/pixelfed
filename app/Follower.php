<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $following_id
 * @property bool|null $local_profile
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile $actor
 * @property-read Profile $target
 * @property-read Profile $profile
 */
class Follower extends Model
{
    protected $fillable = ['profile_id', 'following_id', 'local_profile'];

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
