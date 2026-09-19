<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $hashtag_id
 * @property int $group_id
 * @property int $profile_id
 * @property int|null $status_id
 * @property string|null $status_visibility
 * @property int $nsfw
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereHashtagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupPostHashtag whereStatusVisibility($value)
 *
 * @mixin \Eloquent
 */
class GroupPostHashtag extends Model
{
    use HasFactory;

    public $fillable = [
        'group_id',
        'group_post_id',
        'status_id',
        'hashtag_id',
        'profile_id',
        'nsfw',
    ];

    public $timestamps = false;
}
