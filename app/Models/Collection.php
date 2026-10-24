<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $profile_id
 * @property string|null $title
 * @property string|null $description
 * @property int $is_nsfw
 * @property string $visibility
 * @property string|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CollectionItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Status> $posts
 * @property-read int|null $posts_count
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Collection whereVisibility($value)
 *
 * @mixin \Eloquent
 */
class Collection extends Model
{
    use HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public $fillable = ['profile_id', 'published_at'];

    public $dates = ['published_at'];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function items()
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function posts()
    {
        return $this->hasManyThrough(
            Status::class,
            CollectionItem::class,
            'collection_id',
            'id',
            'id',
            'object_id'
        );
    }

    public function url()
    {
        return url("/c/{$this->id}");
    }
}
