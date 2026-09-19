<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $user_id
 * @property string $service
 * @property string|null $post_hash
 * @property string $filename
 * @property int $media_count
 * @property string|null $post_type
 * @property string|null $caption
 * @property array<array-key, mixed>|null $media
 * @property int|null $creation_year
 * @property int|null $creation_month
 * @property int|null $creation_day
 * @property int|null $creation_id
 * @property int|null $status_id
 * @property Carbon|null $creation_date
 * @property array<array-key, mixed>|null $metadata
 * @property int $skip_missing_media
 * @property int $uploaded_to_s3
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreationDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreationMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereCreationYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereMediaCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost wherePostHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost wherePostType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereSkipMissingMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereUploadedToS3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportPost whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ImportPost extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'creation_date' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function status(): HasOne
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
}
