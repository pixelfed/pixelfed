<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function url()
    {
        return url('/i/message/' . $this->to_id . '/' . $this->id);
    }

    public function author()
    {
        return $this->hasOne(Profile::class, 'id', 'from_id');
    }

    public function me()
    {
        return Auth::user()->profile->id === $this->from_id;
    }
}
