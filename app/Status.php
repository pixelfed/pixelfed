<?php

namespace App;

use Auth, Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
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

    public function viewType()
    {
      $media = $this->firstMedia();
      $type = explode('/', $media->mime);
      return $type[0];
    }
    
    public function thumb($showNsfw = false)
    {
      $type = $this->viewType();
      $is_nsfw = !$showNsfw ? $this->is_nsfw : false;
      if($this->media->count() == 0 || $is_nsfw || $type != 'image') {
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

    public function permalink($suffix = '/activity')
    {
      $id = $this->id;
      $username = $this->profile->username;
      $path = config('app.url') . "/p/{$username}/{$id}{$suffix}";
      return url($path);
    }

    public function editUrl()
    {
      return $this->url() . '/edit';
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

    public function liked() : bool
    {
      $profile = Auth::user()->profile;
      return Like::whereProfileId($profile->id)->whereStatusId($this->id)->count();
    }

    public function comments()
    {
      return $this->hasMany(Status::class, 'in_reply_to_id');
    }

    public function bookmarked()
    {
      if(!Auth::check()) {
        return 0;
      }
      $profile = Auth::user()->profile;
      return Bookmark::whereProfileId($profile->id)->whereStatusId($this->id)->count();
    }

    public function shares()
    {
      return $this->hasMany(Status::class, 'reblog_of_id');
    }

    public function shared() : bool
    {
      $profile = Auth::user()->profile;
      return Status::whereProfileId($profile->id)->whereReblogOfId($this->id)->count();
    }

    public function parent()
    {
      $parent = $this->in_reply_to_id ?? $this->reblog_of_id;
      if(!empty($parent)) {
        return Status::findOrFail($parent);
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

    public function mentions()
    {
      return $this->hasManyThrough(
        Profile::class,
        Mention::class,
        'status_id',
        'id',
        'id',
        'profile_id'
      );
    }

    public function reportUrl()
    {
      return route('report.form') . "?type=post&id={$this->id}";
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

    public function recentComments()
    {
      return $this->comments()->orderBy('created_at','desc')->take(3);
    }
}
