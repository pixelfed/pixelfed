<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigCache extends Model
{
    use HasFactory;

    protected $table = 'config_cache';

    public $guarded = [];
}
