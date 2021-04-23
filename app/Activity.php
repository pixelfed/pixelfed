<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $dates = ['processed_at'];
    protected $fillable = ['data', 'to_id', 'from_id', 'object_type'];

	public function toProfile()
	{
		return $this->belongsTo(Profile::class, 'to_id');
	}

	public function fromProfile()
	{
		return $this->belongsTo(Profile::class, 'from_id');
	}
}
