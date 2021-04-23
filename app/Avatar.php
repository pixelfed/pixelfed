<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
    
    protected $fillable = ['profile_id'];

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
