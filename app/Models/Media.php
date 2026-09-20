<?php

namespace App\Models;

use App\Util\Media\License;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $status_id
 * @property int|null $profile_id
 * @property int|null $user_id
 * @property int $is_nsfw
 * @property int $remote_media
 * @property string|null $original_sha256
 * @property string|null $optimized_sha256
 * @property string $media_path
 * @property string|null $thumbnail_path
 * @property string|null $cdn_url
 * @property string|null $optimized_url
 * @property string|null $thumbnail_url
 * @property string|null $remote_url
 * @property string|null $caption
 * @property string|null $hls_path
 * @property int $order
 * @property string|null $mime
 * @property int|null $size
 * @property string|null $orientation
 * @property string|null $filter_name
 * @property string|null $filter_class
 * @property string|null $license
 * @property string|null $processed_at
 * @property string|null $hls_transcoded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $key
 * @property string|null $metadata
 * @property int $version
 * @property string|null $blurhash
 * @property array<array-key, mixed>|null $srcset
 * @property int|null $width
 * @property int|null $height
 * @property bool|null $skip_optimize
 * @property string|null $replicated_at
 * @property-read Profile|null $profile
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereBlurhash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCdnUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFilterClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFilterName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereHlsPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereHlsTranscodedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereMediaPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOptimizedSha256($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOptimizedUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOrientation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOriginalSha256($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereRemoteMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereReplicatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereSkipOptimize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereSrcset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereThumbnailPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Media extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'srcset' => 'array',
            'deleted_at' => 'datetime',
            'skip_optimize' => 'boolean',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url()
    {
        if ($this->cdn_url) {
            // return Storage::disk(config('filesystems.cloud'))->url($this->media_path);
            return $this->cdn_url;
        }

        if ($this->remote_media && $this->remote_url) {
            return $this->remote_url;
        }

        return url(Storage::url($this->media_path));
    }

    public function thumbnailUrl()
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        if (! $this->remote_media && $this->thumbnail_path) {
            return url(Storage::url($this->thumbnail_path));
        }

        if (! $this->thumbnail_path && $this->cdn_url) {
            return $this->cdn_url;
        }

        if ($this->media_path && $this->mime && in_array($this->mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
            return $this->remote_media || Str::startsWith($this->media_path, 'http') ?
                $this->media_path :
                url(Storage::url($this->media_path));
        }

        return url(Storage::url('public/no-preview.png'));
    }

    public function thumb()
    {
        return $this->thumbnailUrl();
    }

    public function mimeType()
    {
        if (! $this->mime) {
            return null;
        }

        return explode('/', $this->mime)[0];
    }

    public function activityVerb(): string
    {
        $verb = 'Document';
        switch ($this->mimeType()) {
            case 'audio':
                $verb = 'Audio';
                break;

            case 'image':
                $verb = 'Document';
                break;

            case 'video':
                $verb = 'Video';
                break;

            default:
                $verb = 'Document';
                break;
        }

        return $verb;
    }

    public function mediaType(): string
    {
        $verb = 'Document';
        switch ($this->mimeType()) {
            case 'audio':
                $verb = 'Audio';
                break;

            case 'image':
                $verb = 'Image';
                break;

            case 'video':
                $verb = 'Video';
                break;

            default:
                $verb = 'Image';
                break;
        }

        return $verb;
    }

    public function getMetadata()
    {
        return json_decode($this->metadata, true, 3);
    }

    public function getModel()
    {
        if (empty($this->metadata)) {
            return false;
        }
        $meta = $this->getMetadata();
        if ($meta && isset($meta['Model'])) {
            return $meta['Model'];
        }
    }

    public function getLicense()
    {
        $license = $this->license;

        if (! $license || strlen($license) > 2 || $license == 1) {
            return null;
        }

        if (! in_array($license, License::keys())) {
            return null;
        }

        $res = License::get()[$license];

        return [
            'id' => $res['id'],
            'title' => $res['title'],
            'url' => $res['url'],
        ];
    }
}
