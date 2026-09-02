<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class AccountLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
