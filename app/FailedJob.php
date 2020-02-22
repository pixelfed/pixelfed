<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FailedJob extends Model
{
    const CREATED_AT = 'failed_at';
    const UPDATED_AT = 'failed_at';

    public $timestamps = 'failed_at';

    public function getFailedAtAttribute($val)
    {
        return Carbon::parse($val);
    }
}
