<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $story_id
 * @property int $profile_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Story|null $story
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView whereStoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StoryView whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StoryView extends Model
{
    public $fillable = ['story_id', 'profile_id'];

    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
