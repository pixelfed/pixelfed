<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $group_id
 * @property int|null $profile_id
 * @property string|null $name
 * @property string $type
 * @property string|null $tags
 * @property string|null $location
 * @property string|null $description
 * @property string|null $metadata
 * @property int $open
 * @property int $comments_open
 * @property int $show_guest_list
 * @property string|null $start_at
 * @property string|null $end_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereCommentsOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereShowGuestList($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupEvent whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupEvent extends Model
{
    use HasFactory;
}
