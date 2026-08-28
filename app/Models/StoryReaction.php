<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryReaction extends Model
{
    public function story()
    {
        return $this->belongsTo(Story::class);
    }
}
