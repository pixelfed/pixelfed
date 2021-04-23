<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class HashtagFollow extends Model
{
    protected $fillable = [
    	'user_id',
    	'profile_id',
    	'hashtag_id'
    ];

    public function hashtag()
    {
    	return $this->belongsTo(Hashtag::class);
    }
}
