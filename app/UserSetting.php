<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'compose_settings' => 'json',
            'other' => 'json',
        ];
    }
}
