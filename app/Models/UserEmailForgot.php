<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmailForgot extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'email_sent_at' => 'datetime',
    ];
}
