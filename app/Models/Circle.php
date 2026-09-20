<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $name
 * @property string|null $description
 * @property string $scope
 * @property int $bcc
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Profile> $members
 * @property-read int|null $members_count
 * @property-read Profile|null $owner
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Circle whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Circle extends Model
{
    protected $guarded = [];

    public function members()
    {
        return $this->hasManyThrough(
            Profile::class,
            CircleProfile::class,
            'circle_id',
            'id',
            'id',
            'profile_id'
        );
    }

    public function owner()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function url()
    {
        return url("/i/circle/show/{$this->id}");
    }
}
