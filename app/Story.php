<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
	protected $visible = ['id'];

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}

	public function items()
	{
		return $this->hasMany(StoryItem::class);
	}

	public function reactions()
	{
		return $this->hasMany(StoryReaction::class);
	}

	public function views()
	{
		return $this->hasMany(StoryView::class);
	}

	public function seen($pid = false)
	{
		$id = $pid ?? Auth::user()->profile->id;
		return $this->views()->whereProfileId($id)->exists();
	}
}
