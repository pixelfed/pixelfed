<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFilter extends Model
{
    protected $fillable = [
        'user_id',
        'filterable_id',
        'filterable_type',
        'filter_type',
    ];
}
