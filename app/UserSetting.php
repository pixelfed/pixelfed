<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
	protected $fillable = ['user_id'];

	protected $casts = [
		'compose_settings' => 'json',
		'other' => 'json'
	];
}
