<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportLog extends Model
{
    protected $guarded = [];

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}
}
