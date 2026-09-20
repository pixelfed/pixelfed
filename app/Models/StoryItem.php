<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Pixelfed\Snowflake\HasSnowflakePrimary;

/**
 * @property Story|null $story
 * @property string|null $type
 * @property Carbon $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryItem query()
 *
 * @mixin \Eloquent
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
