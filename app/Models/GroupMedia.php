<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class GroupMedia extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'processed_at' => 'datetime',
            'thumbnail_generated' => 'datetime',
        ];
    }

    public function url()
    {
        if ($this->cdn_url) {
            return $this->cdn_url;
        }

        return Storage::url($this->media_path);
    }

    public function thumbnailUrl()
    {
        return $this->thumbnail_url;
    }
}
