<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Profile;

class ProfileAlias extends Model
{
    use HasFactory;

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
