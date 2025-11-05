<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Like extends Model
{
    use SoftDeletes;

    const MAX_PER_DAY = 1500;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
    	'deleted_at' => 'datetime'
    ];

    protected $fillable = ['profile_id', 'status_id', 'status_profile_id'];
}
