<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $dates = ['processed_at'];
    protected $fillable = ['data', 'to_id', 'from_id', 'object_type'];

	public function toProfile()
	{
		return $this->belongsTo(Profile::class, 'to_id');
	}

	public function fromProfile()
	{
		return $this->belongsTo(Profile::class, 'from_id');
	}
}
