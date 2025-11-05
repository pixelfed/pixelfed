<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

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
