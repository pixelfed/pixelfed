<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $name
 * @property string $slug
 * @property int $active
 * @property int $order
 * @property int|null $media_id
 * @property int $no_nsfw
 * @property int $local_only
 * @property int $public_only
 * @property int $photos_only
 * @property string|null $active_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Hashtag> $hashtags
 * @property-read int|null $hashtags_count
 * @property-read Collection<int, DiscoverCategoryHashtag> $items
 * @property-read int|null $items_count
 * @property-read Media|null $media
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereActiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereLocalOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereNoNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory wherePhotosOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory wherePublicOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DiscoverCategory extends Model
{
    protected $guarded = [];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function url()
    {
        return url('/discover/c/'.$this->slug);
    }

    public function editUrl()
    {
        return url('/i/admin/discover/category/edit/'.$this->id);
    }

    public function thumb()
    {
        return $this->media->thumb();
    }

    public function mediaUrl()
    {
        return $this->media->url();
    }

    public function items()
    {
        return $this->hasMany(DiscoverCategoryHashtag::class, 'discover_category_id');
    }

    public function hashtags()
    {
        return $this->hasManyThrough(
            Hashtag::class,
            DiscoverCategoryHashtag::class,
            'discover_category_id',
            'id',
            'id',
            'hashtag_id'
        );
    }

    public function posts()
    {
        return Status::select('*')
            ->join('status_hashtags', 'statuses.id', '=', 'status_hashtags.status_id')
            ->join('hashtags', 'status_hashtags.hashtag_id', '=', 'hashtags.id')
            ->join('discover_category_hashtags', 'hashtags.id', '=', 'discover_category_hashtags.hashtag_id')
            ->join('discover_categories', 'discover_category_hashtags.discover_category_id', '=', 'discover_categories.id')
            ->where('discover_categories.id', $this->id);
    }
}
