<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoryItem extends Model
{
	public function story()
	{
		return $this->belongsTo(Story::class);
	}
}
