<?php

namespace App\Models;

use App\Profile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
