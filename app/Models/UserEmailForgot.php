<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class UserEmailForgot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
        ];
    }
}
