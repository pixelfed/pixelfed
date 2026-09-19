<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int|null $profile_id
 * @property int|null $status_id
 * @property int|null $in_reply_to_id
 * @property string|null $remote_url
 * @property string|null $caption
 * @property int $is_nsfw
 * @property string|null $visibility
 * @property int $likes_count
 * @property int $replies_count
 * @property string|null $cw_summary
 * @property string|null $media_ids
 * @property string|null $status
 * @property string|null $type
 * @property int $local
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereCwSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereInReplyToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereLikesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereMediaIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereRepliesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupComment whereVisibility($value)
 *
 * @mixin \Eloquent
 */
class GroupComment extends Model
{
    use HasFactory;

    public $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url(): string
    {
        return '/group/'.$this->group_id.'/c/'.$this->id;
    }
}
