<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Circle extends Model
{
    protected $fillable = [
        'profile_id',
    	'name',
    	'description',
    	'bcc',
    	'scope',
    	'active'
    ];

    public function members()
    {
    	return $this->hasManyThrough(
    		Profile::class,
    		CircleProfile::class,
    		'circle_id',
    		'id',
    		'id',
    		'profile_id'
    	);
    }

    public function owner()
    {
    	return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function url()
    {
        return url("/i/circle/show/{$this->id}");
    }
}
