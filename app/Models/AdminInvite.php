<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminInvite extends Model
{
    use HasFactory;

    protected $casts = [
        'used_by' => 'array',
        'expires_at' => 'datetime',
    ];

    public function url()
    {
        return url('/auth/invite/a/' . $this->invite_code);
    }
}
