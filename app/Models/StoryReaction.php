<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read Story|null $story
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryReaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryReaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryReaction query()
 *
 * @mixin \Eloquent
 */
class StoryReaction extends Model
{
    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
