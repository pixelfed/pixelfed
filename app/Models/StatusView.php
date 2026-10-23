<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $status_id
 * @property int|null $status_profile_id
 * @property int|null $profile_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereStatusProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusView whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StatusView extends Model
{
    use HasFactory;

    protected $guarded = [];
}
