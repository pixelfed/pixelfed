<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\User;

class UserRoles extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'roles' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
