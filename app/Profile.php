<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;

class Profile extends Model
{
    protected $hidden = [
        'private_key',
    ];

    protected $visible = ['id', 'username', 'name'];

    public function url($suffix = '')
    {
        return url('/@' . $this->username . $suffix);
    }

    public function permalink($suffix = '')
    {
        return url('users/' . $this->username . $suffix);
    }
    
    public function emailUrl()
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        return $this->username . '@' . $domain;
    }

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

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function avatar()
    {
        return $this->hasOne(Avatar::class);
    }

    public function avatarUrl()
    {
        $url = url(Storage::url($this->avatar->media_path ?? 'public/avatars/default.png'));
        return $url;
    }
}
