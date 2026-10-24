<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $can_trend
 * @property int|null $can_search
 * @property int $is_nsfw
 * @property int $is_banned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $cached_count
 * @property-read Collection<int, Status> $posts
 * @property-read int|null $posts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereCachedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereCanSearch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereCanTrend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Hashtag extends Model
{
    public $fillable = ['name', 'slug'];

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Status::class,
            StatusHashtag::class,
            'hashtag_id',
            'id',
            'id',
            'status_id'
        );
    }

    public function url($suffix = ''): string
    {
        return config('routes.hashtag.base').$this->slug.$suffix;
    }
}
