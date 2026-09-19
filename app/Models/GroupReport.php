<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $profile_id
 * @property string|null $type
 * @property string|null $item_type
 * @property string|null $item_id
 * @property string|null $metadata
 * @property int $open
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupReport whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupReport extends Model
{
    use HasFactory;
}
