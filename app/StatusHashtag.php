<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class StatusHashtag extends Model
{
    public $fillable = [
        'status_id',
        'hashtag_id',
        'profile_id',
        'status_visibility',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function hashtag(): BelongsTo
    {
        return $this->belongsTo(Hashtag::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(
            Media::class,
            Status::class,
            'id',
            'status_id',
            'status_id',
            'id'
        );
    }
}
