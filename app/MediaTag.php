<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediaTag extends Model
{
    public function status()
    {
    	return $this->belongsTo(Status::class);
    }
}
