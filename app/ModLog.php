<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModLog extends Model
{
	protected $visible = ['id'];

	protected $fillable = ['*'];
}
