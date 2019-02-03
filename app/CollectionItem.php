<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CollectionItem extends Model
{
	public function collection()
	{
		return $this->belongsTo(Collection::class);
	}
}
