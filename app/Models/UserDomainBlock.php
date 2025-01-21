<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Profile;

class UserDomainBlock extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}
