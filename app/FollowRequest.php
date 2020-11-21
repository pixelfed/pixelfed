<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FollowRequest extends Model
{
	protected $fillable = ['follower_id', 'following_id'];
	
    public function follower()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function following()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'follower_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'following_id', 'id');
    }
}
