<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $dates = ['processed_at'];

    public function toProfile()
    {
        return $this->belongsTo(Profile::class, 'to_id');
    }

    public function fromProfile()
    {
        return $this->belongsTo(Profile::class, 'from_id');
    }
}
