<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;

class Place extends Model
{
	protected $visible = ['id', 'name', 'country', 'slug'];

	public function url()
	{
		return url('/discover/places/' . $this->id . '/' . $this->slug);
	}

	public function posts()
	{
		return $this->hasMany(Status::class);
	}

	public function postCount()
	{
		return $this->posts()->count();
	}

	public function statuses()
	{
		return $this->hasMany(Status::class, 'id', 'place_id');
	}
}
