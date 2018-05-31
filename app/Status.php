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

    public function thumb()
    {
      return url(Storage::url($this->firstMedia()->thumbnail_path));
    }

    public function url()
    {
      $id = $this->id;
      $username = $this->profile->username;
      return url(config('app.url') . "/p/{$username}/{$id}");
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
      return $this->hasMany(Status::class, 'in_reply_to_id');
    }

    public function parent()
    {
      if(!empty($this->in_reply_to_id)) {
        return Status::findOrFail($this->in_reply_to_id);
      }
    }

    public function conversation()
    {
      return $this->hasOne(Conversation::class);
    }

    public function hashtags()
    {
      return $this->hasManyThrough(
        Hashtag::class,
        StatusHashtag::class,
        'status_id',
        'id',
        'id',
        'hashtag_id'
      );
    }

    public function toActivityStream()
    {
      $media = $this->media;
      $mediaCollection = [];
      foreach($media as $image) {
        $mediaCollection[] = [
          "type" => "Link",
          "href" => $image->url(),
          "mediaType" => $image->mime
        ];
      }
      $obj = [
        "@context" => "https://www.w3.org/ns/activitystreams",
        "type" => "Image",
        "name" => null,
        "url" => $mediaCollection
      ];
      return $obj;
    }

}
