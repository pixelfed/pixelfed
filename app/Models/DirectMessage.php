<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property int $to_id
 * @property int $from_id
 * @property string|null $type
 * @property string|null $from_profile_ids
 * @property int $group_message
 * @property int $is_hidden
 * @property string|null $meta
 * @property int $status_id
 * @property string|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $author
 * @property-read Profile|null $recipient
 * @property-read Status|null $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereFromProfileIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereGroupMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DirectMessage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DirectMessage extends Model
{
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function url(): string
    {
        return config('app.url').'/account/direct/m/'.$this->status_id;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'from_id', 'id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'to_id', 'id');
    }

    public function me(): bool
    {
        return Auth::user()->profile->id === $this->from_id;
    }
}
