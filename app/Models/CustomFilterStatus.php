<?php

namespace App\Models;

use App\Status;
use Illuminate\Database\Eloquent\Model;

class CustomFilterStatus extends Model
{
    protected $fillable = [
        'custom_filter_id', 'status_id',
    ];
}
