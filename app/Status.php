<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;

class Status extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = ['profile_id', 'visibility'];

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
        $mime = explode('/', $media->mime)[0];
        $count = $this->media()->count();
        $type = ($mime == 'image') ? 'image' : 'video';
        if($count > 1) {
            $type = ($type == 'image') ? 'album' : 'video-album';
        }

        return $type;
    }

    public function thumb($showNsfw = false)
    {
        $type = $this->viewType();
        $is_nsfw = !$showNsfw ? $this->is_nsfw : false;
        if ($this->media->count() == 0 || $is_nsfw || !in_array($type,['image', 'album', 'video'])) {
            return 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';
        }

        return url(Storage::url($this->firstMedia()->thumbnail_path));
    }

    public function url()
    {
        $id = $this->id;
        $username = $this->profile->username;
        $path = config('app.url')."/p/{$username}/{$id}";
        if (!is_null($this->in_reply_to_id)) {
            $pid = $this->in_reply_to_id;
            $path = "{$this->parent()->url()}#comment-{$id}";
        }

        return url($path);
    }

    public function permalink($suffix = '/activity')
    {
        $id = $this->id;
        $username = $this->profile->username;
        $path = config('app.url')."/p/{$username}/{$id}{$suffix}";

        return url($path);
    }

    public function editUrl()
    {
        return $this->url().'/edit';
    }

    public function mediaUrl()
    {
        $media = $this->firstMedia();
        $path = $media->media_path;
        $hash = is_null($media->processed_at) ? md5('unprocessed') : md5($media->created_at);
        $url = Storage::url($path)."?v={$hash}";

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
        return $this->hasMany(self::class, 'in_reply_to_id');
    }

    public function bookmarked()
    {
        if (!Auth::check()) {
            return 0;
        }
        $profile = Auth::user()->profile;

        return Bookmark::whereProfileId($profile->id)->whereStatusId($this->id)->count();
    }

    public function shares()
    {
        return $this->hasMany(self::class, 'reblog_of_id');
    }

    public function shared() : bool
    {
        $profile = Auth::user()->profile;

        return self::whereProfileId($profile->id)->whereReblogOfId($this->id)->count();
    }

    public function parent()
    {
        $parent = $this->in_reply_to_id ?? $this->reblog_of_id;
        if (!empty($parent)) {
            return self::findOrFail($parent);
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
        return route('report.form')."?type=post&id={$this->id}";
    }

    public function toActivityStream()
    {
        $media = $this->media;
        $mediaCollection = [];
        foreach ($media as $image) {
            $mediaCollection[] = [
          'type'      => 'Link',
          'href'      => $image->url(),
          'mediaType' => $image->mime,
        ];
        }
        $obj = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'type'     => 'Image',
        'name'     => null,
        'url'      => $mediaCollection,
      ];

        return $obj;
    }

    public function replyToText()
    {
        $actorName = $this->profile->username;

        return "{$actorName} ".__('notification.commented');
    }

    public function replyToHtml()
    {
        $actorName = $this->profile->username;
        $actorUrl = $this->profile->url();

        return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> ".
          __('notification.commented');
    }

    public function recentComments()
    {
        return $this->comments()->orderBy('created_at', 'desc')->take(3);
    }

    public function toActivityPubObject()
    {
        if($this->local == false) {
            return;
        }
        $profile = $this->profile;
        $to = $this->scopeToAudience('to');
        $cc = $this->scopeToAudience('cc');
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'    => $this->permalink(),
            'type'  => 'Create',
            'actor' => $profile->permalink(),
            'published' => $this->created_at->format('c'),
            'to' => $to,
            'cc' => $cc,
            'object' => [
                'id' => $this->url(),
                'type' => 'Note',
                'summary' => null,
                'inReplyTo' => null,
                'published' => $this->created_at->format('c'),
                'url' => $this->url(),
                'attributedTo' => $this->profile->url(),
                'to' => $to,
                'cc' => $cc,
                'sensitive' => (bool) $this->is_nsfw,
                'content' => $this->rendered,
                'attachment' => $this->media->map(function($media) {
                    return [
                        'type' => 'Document',
                        'mediaType' => $media->mime,
                        'url' => $media->url(),
                        'name' => null
                    ];
                })
            ]
        ];
    }

    public function scopeToAudience($audience)
    {
        if(!in_array($audience, ['to', 'cc']) || $this->local == false) { 
            return;
        }
        $res = [];
        $res['to'] = [];
        $res['cc'] = [];
        $scope = $this->scope;
        switch ($scope) {
            case 'public':
                $res['to'] = [
                    "https://www.w3.org/ns/activitystreams#Public"
                ];
                $res['cc'] = [
                    $this->profile->permalink('/followers')
                ];
                break;
            
            default:
                # code...
                break;
        }
        return $res[$audience];
    }

}
