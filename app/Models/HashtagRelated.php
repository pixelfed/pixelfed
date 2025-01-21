<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashtagRelated extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates and other custom formats.
     *
     * @var array
     */
    protected $casts = [
        'related_tags' => 'array',
        'last_calculated_at' => 'datetime',
        'last_moderated_at' => 'datetime',
    ];
}
