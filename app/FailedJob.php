<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
