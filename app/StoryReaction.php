<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoryReaction extends Model
{
    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
