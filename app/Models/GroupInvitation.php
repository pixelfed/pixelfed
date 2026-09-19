<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $from_profile_id
 * @property int $to_profile_id
 * @property string|null $role
 * @property int $to_local
 * @property int $from_local
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereFromLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereFromProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereToLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereToProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInvitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupInvitation extends Model
{
    use HasFactory;
}
