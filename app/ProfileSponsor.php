<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfileSponsor extends Model
{
    public $fillable = ['profile_id'];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
