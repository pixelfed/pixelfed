<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;

class StoryItem extends Model
{
	public function story()
	{
		return $this->belongsTo(Story::class);
	}

	public function url()
	{
		return Storage::url($this->media_path);
	}
}
