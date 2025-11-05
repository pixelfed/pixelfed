<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CustomFilter;

class CustomFilterKeyword extends Model
{
    protected $fillable = [
        'keyword', 'whole_word', 'custom_filter_id',
    ];

    protected $casts = [
        'whole_word' => 'boolean',
    ];
}
