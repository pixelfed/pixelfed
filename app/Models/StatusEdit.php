<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $status_id
 * @property int $profile_id
 * @property string|null $caption
 * @property string|null $spoiler_text
 * @property array<array-key, mixed>|null $ordered_media_attachment_ids
 * @property array<array-key, mixed>|null $media_descriptions
 * @property array<array-key, mixed>|null $poll_options
 * @property int|null $is_nsfw
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereMediaDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereOrderedMediaAttachmentIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit wherePollOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereSpoilerText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusEdit whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StatusEdit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ordered_media_attachment_ids' => 'array',
            'media_descriptions' => 'array',
            'poll_options' => 'array',
        ];
    }
}
