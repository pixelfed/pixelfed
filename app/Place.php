<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;

class Place extends Model
{
	use HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
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
}
