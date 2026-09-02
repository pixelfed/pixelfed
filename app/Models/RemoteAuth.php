<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class RemoteAuth extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'verify_credentials' => 'array',
            'last_successful_login_at' => 'datetime',
            'last_verify_credentials_at' => 'datetime',
        ];
    }
}
