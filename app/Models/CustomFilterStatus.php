<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $custom_filter_id
 * @property int $status_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomFilter $customFilter
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereCustomFilterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CustomFilterStatus extends Model
{
    protected $guarded = [];

    public function customFilter()
    {
        return $this->belongsTo(CustomFilter::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
