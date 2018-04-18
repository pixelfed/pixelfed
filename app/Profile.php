<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    public function statuses()
    {
      return $this->hasMany(Status::class);
    }

    public function following()
    {
      return $this->hasManyThrough(
        Profile::class,
        Follower::class,
        'profile_id',
        'id',
        'id',
        'id'
      );
    }

    public function followers()
    {
      return $this->hasManyThrough(
        Profile::class,
        Follower::class,
        'following_id',
        'id',
        'id',
        'id'
      );
    }
}
