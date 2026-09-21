<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $profile_id
 * @property string $type
 * @property string|null $body
 * @property array|null $entities
 * @property array|null $meta
 * @property bool $is_sensitive
 * @property string|null $ap_object_uri
 * @property string|null $ap_object_hash
 * @property int|null $in_reply_to_id
 * @property int|null $status_id
 * @property int|null $legacy_dm_id
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class DmMessage extends Model
{
    use HasSnowflakePrimary, SoftDeletes;

    public const TYPE_TEXT = 'text';

    public const TYPE_EMOJI = 'emoji';

    public const TYPE_LINK = 'link';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_PHOTOS = 'photos';

    public const TYPE_VIDEO = 'video';

    public const TYPE_VIDEOS = 'videos';

    public const TYPE_MEDIA = 'media';

    public const TYPE_STORY_REACT = 'story:react';

    public const TYPE_STORY_COMMENT = 'story:comment';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'entities' => 'array',
            'meta' => 'array',
            'is_sensitive' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DmMessage $message) {
            if ($message->isDirty('ap_object_uri')) {
                $message->ap_object_hash = $message->ap_object_uri
                    ? self::hashUri($message->ap_object_uri)
                    : null;
            }
        });
    }

    /**
     * Object ids are looked up by hash so the unique index never depends on
     * how long a remote server makes its ids.
     */
    public static function hashUri(string $uri): string
    {
        return hash('sha256', $uri);
    }

    protected function scopeWhereObjectUri(Builder $query, string $uri): Builder
    {
        return $query->where('ap_object_hash', self::hashUri($uri));
    }

    public static function localObjectUri(int|string $id): string
    {
        return url('/i/dm/messages/'.$id);
    }

    /**
     * The id this message is known by on the network.
     */
    public function objectUri(): string
    {
        return $this->ap_object_uri ?: self::localObjectUri($this->id);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DmConversation::class, 'conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'in_reply_to_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'dm_message_media', 'message_id', 'media_id')
            ->withPivot('position')
            ->orderBy('dm_message_media.position');
    }
}
