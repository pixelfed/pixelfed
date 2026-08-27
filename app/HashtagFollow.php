<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HashtagFollow extends Model
{
    protected $fillable = [
        'user_id',
        'profile_id',
        'hashtag_id',
    ];

    const MAX_LIMIT = 25;

    public function hashtag(): BelongsTo
    {
        return $this->belongsTo(Hashtag::class);
    }
}
