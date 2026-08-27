<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOidcMapping extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
