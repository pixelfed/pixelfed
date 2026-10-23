<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int|null $item_id
 * @property string|null $item_type
 * @property string|null $action
 * @property int $system_message
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereSystemMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ReportLog extends Model
{
    protected $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
