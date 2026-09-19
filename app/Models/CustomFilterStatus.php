<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $custom_filter_id
 * @property int $status_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomFilter $customFilter
 * @property-read \App\Models\Status|null $status
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereCustomFilterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterStatus whereUpdatedAt($value)
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
