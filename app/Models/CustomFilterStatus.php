<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class CustomFilterStatus extends Model
{
    public function customFilter()
    {
        return $this->belongsTo(CustomFilter::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
