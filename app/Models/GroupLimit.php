<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property int $profile_id
 * @property array<array-key, mixed>|null $limits
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereLimits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupLimit whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupLimit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'limits' => 'json',
            'metadata' => 'json',
        ];
    }
}
