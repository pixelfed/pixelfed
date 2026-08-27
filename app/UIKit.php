<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UIKit extends Model
{
    protected $table = 'uikit';

    protected $guarded = [];

    public static function section($k)
    {
        return (new self)->where('k', $k)->first()->v;
    }
}
