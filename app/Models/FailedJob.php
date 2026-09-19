<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string $connection
 * @property string $queue
 * @property string $payload
 * @property string $exception
 * @property \Illuminate\Support\Carbon $failed_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereConnection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereException($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FailedJob whereUuid($value)
 *
 * @mixin \Eloquent
 */
class FailedJob extends Model
{
    const CREATED_AT = 'failed_at';

    const UPDATED_AT = 'failed_at';

    public $timestamps = 'failed_at';

    public function getFailedAtAttribute($val)
    {
        return Carbon::parse($val);
    }
}
