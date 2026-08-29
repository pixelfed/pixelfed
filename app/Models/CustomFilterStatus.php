<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFilterStatus extends Model
{
    protected $guarded = [];

    public function customFilter()
    {
        return $this->belongsTo(CustomFilter::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
