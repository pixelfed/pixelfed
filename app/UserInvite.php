<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInvite extends Model
{
	public function sender()
	{
		return $this->belongsTo(Profile::class, 'profile_id');
	}

    public function url()
    {
    	return url("/i/invite/code/{$this->key}/{$this->token}");
    }
}
