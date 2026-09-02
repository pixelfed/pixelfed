<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('story_id', 'profile_id')]
class StoryView extends Model
{
    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
