<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Avatar extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'last_fetched_at',
        'last_processed_at'
    ];
    
    protected $guarded = [];

    protected $visible = [
        'id',
        'profile_id',
        'media_path',
        'size',
    ];

    public function profile()
    {
    	return $this->belongsTo(Profile::class);
    }
}
