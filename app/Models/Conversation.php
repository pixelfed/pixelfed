<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $to_id
 * @property int $from_id
 * @property int|null $dm_id
 * @property int|null $status_id
 * @property string|null $type
 * @property int $is_hidden
 * @property int $has_seen
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereDmId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereHasSeen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];
}
