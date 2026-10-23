<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int|null $job_id
 * @property string $service
 * @property string|null $path
 * @property int $stage
 * @property string|null $original_name
 * @property int|null $import_accepted
 * @property string|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereImportAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportData whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ImportData extends Model
{
    protected $table = 'import_datas';
}
