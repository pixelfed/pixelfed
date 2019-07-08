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
}
