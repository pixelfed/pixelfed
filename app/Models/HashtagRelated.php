<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class HashtagRelated extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'related_tags' => 'array',
            'last_calculated_at' => 'datetime',
            'last_moderated_at' => 'datetime',
        ];
    }
}
