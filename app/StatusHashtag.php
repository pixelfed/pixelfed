<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatusHashtag extends Model
{
    public $fillable = [
    	'status_id', 
    	'hashtag_id', 
    	'profile_id',
    	'status_visibility'
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

	public function media()
	{
        return $this->hasManyThrough(
            Media::class,
            Status::class,
            'id',
            'status_id',
            'status_id',
            'id'
        );
	}
}
