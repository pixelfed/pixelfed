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
      if($this->media->count() == 0 || $this->is_nsfw) {
        return "data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==";
      }
      return url(Storage::url($this->firstMedia()->thumbnail_path));
    }

    public function url()
    {
      $id = $this->id;
      $username = $this->profile->username;
      $path = config('app.url') . "/p/{$username}/{$id}";
      if(!is_null($this->in_reply_to_id)) {
        $pid = $this->in_reply_to_id;
        $path = config('app.url') . "/p/{$username}/{$pid}/c/{$id}";
      }
      return url($path);
    }

    public function mediaUrl()
    {
      $media = $this->firstMedia();
      $path = $media->media_path;
      $hash = is_null($media->processed_at) ? md5('unprocessed') : md5($media->created_at); 
      $url = Storage::url($path) . "?v={$hash}";
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

    public function replyToText()
    {
      $actorName = $this->profile->username;
      return "{$actorName} " . __('notification.commented');
    }

    public function replyToHtml()
    {
      $actorName = $this->profile->username;
      $actorUrl = $this->profile->url();
      return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> " .
          __('notification.commented');
    }
}
