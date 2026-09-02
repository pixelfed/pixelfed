<?php

namespace App\Models;

use App\Util\Media\License;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Unguarded]
class Media extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'srcset' => 'array',
            'deleted_at' => 'datetime',
            'skip_optimize' => 'boolean',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url()
    {
        if ($this->cdn_url) {
            // return Storage::disk(config('filesystems.cloud'))->url($this->media_path);
            return $this->cdn_url;
        }

        if ($this->remote_media && $this->remote_url) {
            return $this->remote_url;
        }

        return url(Storage::url($this->media_path));
    }

    public function thumbnailUrl()
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        if (! $this->remote_media && $this->thumbnail_path) {
            return url(Storage::url($this->thumbnail_path));
        }

        if (! $this->thumbnail_path && $this->cdn_url) {
            return $this->cdn_url;
        }

        if ($this->media_path && $this->mime && in_array($this->mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
            return $this->remote_media || Str::startsWith($this->media_path, 'http') ?
                $this->media_path :
                url(Storage::url($this->media_path));
        }

        return url(Storage::url('public/no-preview.png'));
    }

    public function thumb()
    {
        return $this->thumbnailUrl();
    }

    public function mimeType()
    {
        if (! $this->mime) {
            return;
        }

        return explode('/', $this->mime)[0];
    }

    public function activityVerb()
    {
        $verb = 'Document';
        switch ($this->mimeType()) {
            case 'audio':
                $verb = 'Audio';
                break;

            case 'image':
                $verb = 'Document';
                break;

            case 'video':
                $verb = 'Video';
                break;

            default:
                $verb = 'Document';
                break;
        }

        return $verb;
    }

    public function mediaType()
    {
        $verb = 'Document';
        switch ($this->mimeType()) {
            case 'audio':
                $verb = 'Audio';
                break;

            case 'image':
                $verb = 'Image';
                break;

            case 'video':
                $verb = 'Video';
                break;

            default:
                $verb = 'Image';
                break;
        }

        return $verb;
    }

    public function getMetadata()
    {
        return json_decode($this->metadata, true, 3);
    }

    public function getModel()
    {
        if (empty($this->metadata)) {
            return false;
        }
        $meta = $this->getMetadata();
        if ($meta && isset($meta['Model'])) {
            return $meta['Model'];
        }
    }

    public function getLicense()
    {
        $license = $this->license;

        if (! $license || strlen($license) > 2 || $license == 1) {
            return null;
        }

        if (! in_array($license, License::keys())) {
            return null;
        }

        $res = License::get()[$license];

        return [
            'id' => $res['id'],
            'title' => $res['title'],
            'url' => $res['url'],
        ];
    }
}
