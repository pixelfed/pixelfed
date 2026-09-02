<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[WithoutTimestamps]
#[Unguarded]
class UserDomainBlock extends Model
{
    use HasFactory;

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}
