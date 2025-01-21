<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteAuth extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'verify_credentials' => 'array',
        'last_successful_login_at' => 'datetime',
        'last_verify_credentials_at' => 'datetime'
    ];
}
