<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Pixelfed\Snowflake\HasSnowflakePrimary;

/**
 * @property int $id
 * @property int $story_id
 * @property string|null $media_path
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(incrementing: false)]
#[Visible('id')]
class StoryItem extends Model
{
    use HasSnowflakePrimary;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function url()
    {
        return url(Storage::url($this->media_path));
    }
}
