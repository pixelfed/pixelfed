<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $group_id
 * @property int $profile_id
 * @property int|null $status_id
 * @property string $media_path
 * @property string|null $thumbnail_url
 * @property string|null $cdn_url
 * @property string|null $url
 * @property string|null $mime
 * @property int|null $size
 * @property string|null $cw_summary
 * @property string|null $license
 * @property string|null $blurhash
 * @property int $order
 * @property int|null $width
 * @property int|null $height
 * @property int $local_user
 * @property int $is_cached
 * @property int $is_comment
 * @property array<array-key, mixed>|null $metadata
 * @property string $version
 * @property int $skip_optimize
 * @property Carbon|null $processed_at
 * @property Carbon|null $thumbnail_generated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereBlurhash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereCdnUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereCwSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereIsCached($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereIsComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereLocalUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereMediaPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereSkipOptimize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereThumbnailGenerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMedia whereWidth($value)
 *
 * @mixin \Eloquent
 */
class GroupMedia extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'processed_at' => 'datetime',
            'thumbnail_generated' => 'datetime',
        ];
    }

    public function url()
    {
        if ($this->cdn_url) {
            return $this->cdn_url;
        }

        return Storage::url($this->media_path);
    }

    public function thumbnailUrl()
    {
        return $this->thumbnail_url;
    }
}
