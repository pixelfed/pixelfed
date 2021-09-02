<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

	public function countryUrl()
	{
		$country = strtolower($this->country);
		$country = urlencode($country);
		return url('/discover/location/country/' . $country);
	}

	public function cityUrl()
	{
		return $this->url();
	}

	public function getName()
	{
		return $this->name . ', ' . $this->country;
	}
}
