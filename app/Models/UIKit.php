<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Table('uikit')]
#[Unguarded]
class UIKit extends Model
{
    public static function section($k)
    {
        return (new self)->where('k', $k)->first()->v;
    }
}
