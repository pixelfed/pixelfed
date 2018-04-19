<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;
use Vinkla\Hashids\Facades\Hashids;

class Status extends Model
{
    public function profile()
    {
      return $this->belongsTo(Profile::class);
    }

    public function media()
    {
      return $this->hasMany(Media::class);
    }

    public function firstMedia()
    {
      return $this->hasMany(Media::class)->orderBy('order', 'asc')->first();
    }

    public function url()
    {
      $hid = Hashids::encode($this->id);
      $username = $this->profile->username;
      return url("/p/@{$username}/{$hid}");
    }

    public function mediaUrl()
    {
      $path = $this->firstMedia()->media_path;
      $url = Storage::url($path);
      return url($url);
    }

    public function likes()
    {
      return $this->hasMany(Like::class);
    }

    public function comments()
    {
      return $this->hasMany(Comment::class);
    }

}
