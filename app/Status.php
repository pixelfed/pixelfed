<?php

namespace App;

use App\HasSnowflakePrimary;
use App\Http\Controllers\StatusController;
use App\Models\Conversation;
use App\Models\Poll;
use App\Models\StatusEdit;
use App\Services\AccountService;
use App\Services\StatusService;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Storage;

class Status extends Model
{
    use HasSnowflakePrimary, SoftDeletes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    protected $guarded = [];

    const STATUS_TYPES = [
        'text',
        'photo',
        'photo:album',
        'video',
        'video:album',
        'photo:video:album',
        'share',
        'reply',
        'story',
        'story:reply',
        'story:reaction',
        'story:live',
        'loop',
    ];

    const MAX_MENTIONS = 20;

    const MAX_HASHTAGS = 60;

    const MAX_LINKS = 5;

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
        if ($this->type) {
            return $this->type;
        }

        return $this->setType();
    }

    public function setType()
    {
        if (in_array($this->type, self::STATUS_TYPES)) {
            return $this->type;
        }
        $mimes = $this->media->pluck('mime')->toArray();
        $type = StatusController::mimeTypeCheck($mimes);
        if ($type) {
            $this->type = $type;
            $this->save();

            return $type;
        }
    }

    public function thumb($showNsfw = false)
    {
        $entity = StatusService::get($this->id, false);

        if (! $entity || ! isset($entity['media_attachments']) || empty($entity['media_attachments'])) {
            return url(Storage::url('public/no-preview.png'));
        }

        if ((! isset($entity['sensitive']) || $entity['sensitive']) && ! $showNsfw) {
            return url(Storage::url('public/no-preview.png'));
        }

        if (! isset($entity['visibility']) || ! in_array($entity['visibility'], ['public', 'unlisted'])) {
            return url(Storage::url('public/no-preview.png'));
        }

        return collect($entity['media_attachments'])
            ->filter(fn ($media) => $media['type'] == 'image' && in_array($media['mime'], ['image/jpeg', 'image/png', 'image/jpg']))
            ->map(function ($media) {
                if (! Str::endsWith($media['preview_url'], ['no-preview.png', 'no-preview.jpg'])) {
                    return $media['preview_url'];
                }

                return $media['url'];
            })
            ->first() ?? url(Storage::url('public/no-preview.png'));
    }

    public function url($forceLocal = false)
    {
        if ($this->uri) {
            return $forceLocal ? "/i/web/post/_/{$this->profile_id}/{$this->id}" : $this->uri;
        } else {
            $id = $this->id;
            $account = AccountService::get($this->profile_id, true);
            if (! $account || ! isset($account['username'])) {
                return '/404';
            }
            $path = url(config('app.url')."/p/{$account['username']}/{$id}");

            return $path;
        }
    }

    public function permalink($suffix = '/activity')
    {
        $id = $this->id;
        $username = $this->profile->username;
        $path = config('app.url')."/p/{$username}/{$id}{$suffix}";

        return url($path);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function liked(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $pid = Auth::user()->profile_id;

        return Like::select('status_id', 'profile_id')
            ->whereStatusId($this->id)
            ->whereProfileId($pid)
            ->exists();
    }

    public function comments()
    {
        return $this->hasMany(self::class, 'in_reply_to_id');
    }

    public function bookmarked()
    {
        if (! Auth::check()) {
            return false;
        }
        $profile = Auth::user()->profile;

        return Bookmark::whereProfileId($profile->id)->whereStatusId($this->id)->count();
    }

    public function shared(): bool
    {
        if (! Auth::check()) {
            return false;
        }
        $pid = Auth::user()->profile_id;

        return $this->select('profile_id', 'reblog_of_id')
            ->whereProfileId($pid)
            ->whereReblogOfId($this->id)
            ->exists();
    }

    public function parent()
    {
        $parent = $this->in_reply_to_id ?? $this->reblog_of_id;
        if (! empty($parent)) {
            return $this->findOrFail($parent);
        } else {
            return false;
        }
    }

    public function scopeToAudience($audience)
    {
        if (! in_array($audience, ['to', 'cc']) || $this->local == false) {
            return;
        }
        $res = [];
        $res['to'] = [];
        $res['cc'] = [];
        $scope = $this->scope;
        $mentions = $this->mentions->map(function ($mention) {
            return $mention->permalink();
        })->toArray();

        if ($this->in_reply_to_id != null) {
            $parent = $this->parent();
            if ($parent) {
                $mentions = array_merge([$parent->profile->permalink()], $mentions);
            }
        }

        switch ($scope) {
            case 'public':
                $res['to'] = [
                    'https://www.w3.org/ns/activitystreams#Public',
                ];
                $res['cc'] = array_merge([$this->profile->permalink('/followers')], $mentions);
                break;

            case 'unlisted':
                $res['to'] = array_merge([$this->profile->permalink('/followers')], $mentions);
                $res['cc'] = [
                    'https://www.w3.org/ns/activitystreams#Public',
                ];
                break;

            case 'private':
                $res['to'] = array_merge([$this->profile->permalink('/followers')], $mentions);
                $res['cc'] = [];
                break;

                // TODO: Update scope when DMs are supported
            case 'direct':
                $res['to'] = [];
                $res['cc'] = [];
                break;
        }

        return $res[$audience];
    }

    public function edits()
    {
        return $this->hasMany(StatusEdit::class);
    }
}
