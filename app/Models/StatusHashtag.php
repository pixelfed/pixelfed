<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $status_id
 * @property int $hashtag_id
 * @property int|null $profile_id
 * @property string|null $status_visibility
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Hashtag|null $hashtag
 * @property-read Collection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Profile|null $profile
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereHashtagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereStatusVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHashtag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StatusHashtag extends Model
{
    public $fillable = [
        'status_id',
        'hashtag_id',
        'profile_id',
        'status_visibility',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function hashtag(): BelongsTo
    {
        return $this->belongsTo(Hashtag::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(
            Media::class,
            Status::class,
            'id',
            'status_id',
            'status_id',
            'id'
        );
    }
}
