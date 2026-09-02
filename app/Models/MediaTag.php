<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
#[Visible('status_id', 'profile_id', 'tagged_username')]
class MediaTag extends Model
{
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
