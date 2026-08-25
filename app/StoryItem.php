<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Pixelfed\Snowflake\HasSnowflakePrimary;
use Storage;

/**
 * @property int $id
 * @property int $story_id
 * @property string|null $media_path
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StoryItem extends Model
{
    use HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $visible = ['id'];

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
