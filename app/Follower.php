<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Profile;

class Follower extends Model
{

    protected $fillable = ['profile_id', 'following_id', 'local_profile'];

    const MAX_FOLLOWING = 7500;
    const FOLLOW_PER_HOUR = 150;

    public function permalink($append = null)
    {
        $path = $this->actor->permalink("#accepts/follows/{$this->id}{$append}");
        return url($path);
    }
}
