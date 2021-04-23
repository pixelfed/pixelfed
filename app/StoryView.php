<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
	public $fillable = ['story_id', 'profile_id'];
	
	public function story()
	{
		return $this->belongsTo(Story::class);
	}
}
