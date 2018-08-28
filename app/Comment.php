<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
