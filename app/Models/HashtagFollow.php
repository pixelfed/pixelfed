<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $profile_id
 * @property int $hashtag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Hashtag|null $hashtag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereHashtagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagFollow whereUserId($value)
 * @mixin \Eloquent
 */
class HashtagFollow extends Model
{
    protected $guarded = [];

    const MAX_LIMIT = 25;

    public function hashtag(): BelongsTo
    {
        return $this->belongsTo(Hashtag::class);
    }
}
