<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('profile_id')]
class ProfileSponsor extends Model
{
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
