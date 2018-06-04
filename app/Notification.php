<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{

  public function actor()
  {
    return $this->belongsTo(Profile::class, 'actor_id', 'id');
  }

  public function profile()
  {
    return $this->belongsTo(Profile::class, 'profile_id', 'id');
  }

  public function item()
  {
    return $this->morphTo();
  }

  public function status()
  {
    return $this->belongsTo(Status::class, 'item_id', 'id');
  }

}
