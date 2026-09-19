<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int|null $user_id
 * @property int $object_id
 * @property string|null $object_type
 * @property int|null $reported_profile_id
 * @property string|null $type
 * @property string|null $message
 * @property Carbon|null $admin_seen
 * @property int $not_interested
 * @property int $spam
 * @property int $nsfw
 * @property int $abusive
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $reportedUser
 * @property-read Profile|null $reporter
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereAbusive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereAdminSeen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereNotInterested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereReportedProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereSpam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Report whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Report extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'admin_seen' => 'datetime',
        ];
    }

    public function url()
    {
        return url('/i/admin/reports/show/'.$this->id);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function reported()
    {
        $class = $this->object_type;

        switch ($class) {
            case Status::class:
                $column = 'id';
                break;

            default:
                $class = Status::class;
                $column = 'id';
                break;
        }

        return (new $class)->where($column, $this->object_id)->first();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'object_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'reported_profile_id', 'id');
    }
}
