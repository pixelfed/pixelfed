<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
