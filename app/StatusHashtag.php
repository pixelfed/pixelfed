<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatusHashtag extends Model
{
    public $fillable = [
    	'status_id', 
    	'hashtag_id', 
    	'profile_id'
    ];

	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function hashtag()
	{
		return $this->belongsTo(Hashtag::class);
	}

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}
}
