<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountInterstitial extends Model
{
	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $dates = ['read_at', 'appeal_requested_at'];

	public const JSON_MESSAGE = 'Please use web browser to proceed.';

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function status()
	{
		if($this->item_type != 'App\Status') {
			return;
		}
		return $this->hasOne(Status::class, 'id', 'item_id');
	}
}
