<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusView extends Model
{
    use HasFactory;

    protected $fillable = [
    	'status_id',
    	'status_profile_id',
    	'profile_id'
    ];
}
