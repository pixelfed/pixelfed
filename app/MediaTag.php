<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediaTag extends Model
{
    protected $guarded = [];

    public function status()
    {
    	return $this->belongsTo(Status::class);
    }
}
