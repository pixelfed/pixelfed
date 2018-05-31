<?php

namespace App;

use Storage;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $hidden = [
        'private_key',
    ];

    protected $visible = ['id', 'username', 'name'];

    public function url($suffix = '')
    {
        return url($this->username . $suffix);
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

    public function followingCount($short = false)
    {
        $count = $this->following()->count();
        if($short) {
            return PrettyNumber::convert($count);
        } else {
            return $count;
        }
    }

    public function followerCount($short = false)
    {
        $count = $this->followers()->count();
        if($short) {
            return PrettyNumber::convert($count);
        } else {
            return $count;
        }
    }

    public function following()
    {
        return $this->belongsToMany(
            Profile::class,
            'followers',
            'profile_id',
            'following_id'
        );
    }

    public function followers()
    {
        return $this->belongsToMany(
            Profile::class,
            'followers',
            'following_id',
            'profile_id'
        );
    }

    public function follows($profile)
    {
        return Follower::whereProfileId($this->id)->whereFollowingId($profile->id)->count();
    }

    public function followedBy($profile)
    {
        return Follower::whereProfileId($profile->id)->whereFollowingId($this->id)->count();
    }

    public function bookmarks()
    {
        return $this->belongsToMany(
            Status::class,
            'bookmarks',
            'profile_id',
            'status_id'
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
