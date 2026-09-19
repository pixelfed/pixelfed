<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $state
 * @property string $country
 * @property string|null $aliases
 * @property numeric|null $lat
 * @property numeric|null $long
 * @property int $score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $cached_post_count
 * @property string|null $last_checked_at
 * @property-read Collection<int, Status> $posts
 * @property-read int|null $posts_count
 * @property-read Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereAliases($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCachedPostCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereLastCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Place whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Place extends Model
{
    protected $visible = ['id', 'name', 'country', 'slug'];

    public function url()
    {
        return url('/discover/places/'.$this->id.'/'.$this->slug);
    }

    public function posts()
    {
        return $this->hasMany(Status::class);
    }

    public function postCount()
    {
        return $this->posts()->count();
    }

    public function statuses()
    {
        return $this->hasMany(Status::class, 'id', 'place_id');
    }

    public function countryUrl()
    {
        $country = strtolower($this->country);
        $country = urlencode($country);

        return url('/discover/location/country/'.$country);
    }

    public function cityUrl()
    {
        return $this->url();
    }

    public function getName(): string
    {
        return $this->name.', '.$this->country;
    }
}
