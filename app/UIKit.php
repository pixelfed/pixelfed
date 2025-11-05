<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UIKit extends Model
{
    protected $table = 'uikit';
    protected $fillable = [
    	'k',
    	'v',
    	'defv',
    	'dhis'
    ];
}
