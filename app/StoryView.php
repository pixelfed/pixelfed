<?php

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
