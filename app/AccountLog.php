<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountLog extends Model
{

	protected $fillable = ['*'];
	
    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
