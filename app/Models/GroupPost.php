<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int|null $profile_id
 * @property string|null $type
 * @property string|null $remote_url
 * @property int|null $reply_count
 * @property Status|null $status
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $caption
 * @property string|null $visibility
 * @property int $is_nsfw
 * @property int $likes_count
 * @property string|null $cw_summary
 * @property string|null $media_ids
 * @property int $comments_disabled
 * @property-read Group|null $group
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereCommentsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereCwSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereLikesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereMediaIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereReplyCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPost whereVisibility($value)
 *
 * @mixin \Eloquent
 */
class GroupPost extends Model
{
    use HasFactory, HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = [];

    public function mediaPath(): string
    {
        return 'public/g/_v1/'.$this->group_id.'/'.$this->id;
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function url(): string
    {
        return '/groups/'.$this->group_id.'/p/'.$this->id;
    }
}
