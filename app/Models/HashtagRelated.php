<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $hashtag_id
 * @property array<array-key, mixed>|null $related_tags
 * @property int|null $agg_score
 * @property Carbon|null $last_calculated_at
 * @property Carbon|null $last_moderated_at
 * @property int $skip_refresh
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereAggScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereHashtagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereLastCalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereLastModeratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereRelatedTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereSkipRefresh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HashtagRelated whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class HashtagRelated extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'related_tags' => 'array',
            'last_calculated_at' => 'datetime',
            'last_moderated_at' => 'datetime',
        ];
    }
}
