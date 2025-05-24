<?php

namespace App\Models;

use App\Status;
use Illuminate\Database\Eloquent\Model;

class CustomFilterStatus extends Model
{
    protected $fillable = [
        'custom_filter_id', 'status_id',
    ];

    public function customFilter()
    {
        return $this->belongsTo(CustomFilter::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
