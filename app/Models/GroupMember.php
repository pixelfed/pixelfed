<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $profile_id
 * @property string $role
 * @property int $local_group
 * @property int $local_profile
 * @property int $join_request
 * @property string|null $approved_at
 * @property string|null $rejected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Group|null $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereJoinRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereLocalGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereLocalProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMember whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupMember extends Model
{
    use HasFactory;

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
