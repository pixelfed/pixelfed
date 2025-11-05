<?php

namespace App;
use App\Status;
use App\Profile;
use App\Hashtag;
use App\Media;

use Illuminate\Database\Eloquent\Model;

class StatusHashtag extends Model
{
    public $fillable = [
    	'status_id', 
    	'hashtag_id', 
    	'profile_id',
    	'status_visibility'
    ];

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
