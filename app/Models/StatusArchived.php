<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $status_id
 * @property int $profile_id
 * @property string|null $original_scope
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereOriginalScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusArchived whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StatusArchived extends Model
{
    use HasFactory;

    protected $guarded = [];
}
