<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class GroupLimit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'limits' => 'json',
            'metadata' => 'json',
        ];
    }
}
