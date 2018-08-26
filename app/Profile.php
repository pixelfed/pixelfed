<?php

namespace App;

use Auth, Cache, Storage;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $hidden = [
        'private_key',
    ];

    protected $visible = ['id', 'username', 'name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function url($suffix = '')
    {
        if($this->remote_url) {
            return $this->remote_url;
        } else {
            return url($this->username . $suffix);
        }
    }

    public function localUrl($suffix = '')
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
        return $this->hasOne(Avatar::class)->withDefault([
            'media_path' => 'public/avatars/default.png'
        ]);
    }

    public function avatarUrl()
    {
        $url = Cache::remember("avatar:{$this->id}", 1440, function() {
            $path = optional($this->avatar)->media_path;
            $version = hash('sha1', $this->avatar->created_at);
            $path = "{$path}?v={$version}";
            return url(Storage::url($path));
        });
        return $url;
    }

    public function statusCount()
    {
        return $this->statuses()
        ->whereHas('media')
        ->whereNull('in_reply_to_id')
        ->whereNull('reblog_of_id')
        ->count();
    }

    public function recommendFollowers()
    {
        $follows = $this->following()->pluck('followers.id');
        $following = $this->following()
            ->orderByRaw('rand()')
            ->take(3)
            ->pluck('following_id');
        $following->push(Auth::id());
        $following = Follower::whereNotIn('profile_id', $follows)
            ->whereNotIn('following_id', $following)
            ->whereNotIn('following_id', $follows)
            ->whereIn('profile_id', $following)
            ->orderByRaw('rand()')
            ->distinct('id')
            ->limit(3)
            ->pluck('following_id');
        $recommended = [];
        foreach($following as $follow) {
            $recommended[] = Profile::findOrFail($follow);
        }

        return $recommended;
    }

    public function keyId()
    {
        if($this->remote_url) {
            return;
        }
        return $this->permalink('#main-key');
    }
}
