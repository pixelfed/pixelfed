<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $formatted
 * @property int $recommended
 * @property int $sensitive
 * @property int $banned
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereFormatted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereRecommended($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereSensitive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupHashtag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupHashtag extends Model
{
    use HasFactory;

    public $fillable = ['name'];
}
