<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportPost extends Model
{
    use HasFactory;

    protected $casts = [
        'media' => 'array',
        'creation_date' => 'datetime',
        'metadata' => 'json'
    ];
}
