<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $group_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $abilities
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereAbilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupRole whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupRole extends Model
{
    use HasFactory;
}
