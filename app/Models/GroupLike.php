<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $profile_id
 * @property int|null $status_id
 * @property int|null $comment_id
 * @property int $local
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLike whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupLike extends Model
{
    use HasFactory;

    public $fillable = ['group_id', 'status_id', 'profile_id', 'comment_id'];
}
