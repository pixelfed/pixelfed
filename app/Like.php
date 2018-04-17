<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    public function actor()
    {
      return $this->belongsTo(Profile::class);
    }

    public function status()
    {
      return $this->belongsTo(Status::class);
    }
}
