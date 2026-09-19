<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $media_path
 * @property string|null $remote_url
 * @property string|null $cdn_url
 * @property int|null $is_remote
 * @property int|null $size
 * @property int $change_count
 * @property Carbon|null $last_fetched_at
 * @property Carbon|null $last_processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereCdnUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereChangeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereIsRemote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereLastFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereLastProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereMediaPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Avatar withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Avatar extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'last_processed_at' => 'datetime',
        ];
    }

    protected $visible = [
        'id',
        'profile_id',
        'media_path',
        'size',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
