<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;
use Storage;

class StoryItem extends Model
{
	use HasSnowflakePrimary;

	/**
	* Indicates if the IDs are auto-incrementing.
	*
	* @var bool
	*/
	public $incrementing = false;

	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $dates = ['expires_at'];

	protected $visible = ['id'];

	public function story()
	{
		return $this->belongsTo(Story::class);
	}

	public function url()
	{
		return url(Storage::url($this->media_path));
	}
}
