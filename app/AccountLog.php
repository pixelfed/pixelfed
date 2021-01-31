<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountLog extends Model
{

	protected $fillable = ['*'];
	
    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
