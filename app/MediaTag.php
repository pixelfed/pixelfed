<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediaTag extends Model
{
    protected $guarded = [];

    protected $visible = [
        'status_id',
        'profile_id',
        'tagged_username',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
