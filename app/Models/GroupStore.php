<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $group_id
 * @property string $store_key
 * @property string|null $store_value
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereStoreKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereStoreValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupStore whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupStore extends Model
{
    use HasFactory;
}
