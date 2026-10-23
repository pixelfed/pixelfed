<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $to_id
 * @property int|null $from_id
 * @property string|null $object_type
 * @property string|null $data
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $fromProfile
 * @property-read Profile|null $toProfile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Activity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function toProfile()
    {
        return $this->belongsTo(Profile::class, 'to_id');
    }

    public function fromProfile()
    {
        return $this->belongsTo(Profile::class, 'from_id');
    }
}
