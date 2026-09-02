<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class UserSetting extends Model
{
    protected function casts(): array
    {
        return [
            'compose_settings' => 'json',
            'other' => 'json',
        ];
    }
}
