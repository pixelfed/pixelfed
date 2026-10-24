<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $status_id
 * @property int $media_id
 * @property int $profile_id
 * @property string|null $tagged_username
 * @property int $is_public
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereTaggedUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaTag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class MediaTag extends Model
{
    protected $guarded = [];

    protected $visible = [
        'status_id',
        'profile_id',
        'tagged_username',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
