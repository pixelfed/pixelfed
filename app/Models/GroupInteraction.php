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
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupInteraction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupInteraction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
