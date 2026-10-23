<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $active
 * @property int|null $order
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupCategory extends Model
{
    use HasFactory;
}
