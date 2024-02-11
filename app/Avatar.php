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
    protected $casts = [
        'deleted_at' => 'datetime',
        'last_fetched_at' => 'datetime',
        'last_processed_at' => 'datetime',
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
