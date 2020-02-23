<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;

class Media extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url()
    {
        if(!empty($this->remote_media) && $this->remote_url) {
            //$url = \App\Services\MediaProxyService::get($this->remote_url, $this->mime);
            $url = $this->remote_url;
        } else {
            $path = $this->media_path;
            $url = $this->cdn_url ?? config('app.url') . Storage::url($path);
        }

        return $url;
    }

    public function thumbnailUrl()
    {
        $path = $this->thumbnail_path ?? 'public/no-preview.png';
        return url(Storage::url($path));
    }

    public function thumb()
    {
        return $this->thumbnailUrl();
    }

    public function mimeType(): string
    {
        return explode('/', $this->mime)[0];
    }

    public function activityVerb(): string
    {
        $verb = 'Image';
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
                $verb = 'Document';
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
        if(empty($this->metadata)) {
            return false;
        }
        $meta = $this->getMetadata();
        if($meta && isset($meta['Model'])) {
            return $meta['Model'];
        }
    }
}
