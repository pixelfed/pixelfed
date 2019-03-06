<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
	protected $fillable = [
		'user_id',
		'ip',
		'user_agent'
	];

    public $timestamps = [
    	'last_active_at'
    ];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
