<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $report_id
 * @property int $profile_id
 * @property int $user_id
 * @property string $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereUserId($value)
 *
 * @mixin \Eloquent
 */
class ReportComment extends Model
{
    protected $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
