<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{


    public function url()
    {
    	return config('app.url') . '/account/direct/m/' . $this->status_id;
    }
}
