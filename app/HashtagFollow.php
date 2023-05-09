<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HashtagFollow extends Model
{
    protected $fillable = [
    	'user_id',
    	'profile_id',
    	'hashtag_id'
    ];

    const MAX_LIMIT = 250;

    public function hashtag()
    {
    	return $this->belongsTo(Hashtag::class);
    }
}
