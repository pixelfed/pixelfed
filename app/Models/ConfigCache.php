<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $k
 * @property string|null $v
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereK($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigCache whereV($value)
 *
 * @mixin \Eloquent
 */
class ConfigCache extends Model
{
    use HasFactory;

    protected $table = 'config_cache';

    public $guarded = [];
}
