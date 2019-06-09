<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function adminUrl()
    {
    	return url('/i/admin/contact/show/' . $this->id);
    }
}
