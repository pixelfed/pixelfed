<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashtagRelated extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'related_tags' => 'array',
            'last_calculated_at' => 'datetime',
            'last_moderated_at' => 'datetime',
        ];
    }
}
