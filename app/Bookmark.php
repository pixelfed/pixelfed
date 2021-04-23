<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
	protected $fillable = ['profile_id', 'status_id'];

	public function status()
	{
		return $this->belongsTo(Status::class);
	}


	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}
}
