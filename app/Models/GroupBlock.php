<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int|null $admin_id
 * @property int|null $profile_id
 * @property int|null $instance_id
 * @property string|null $name
 * @property string|null $reason
 * @property int $is_user
 * @property int $moderated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereIsUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereModerated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupBlock whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupBlock extends Model
{
    use HasFactory;
}
