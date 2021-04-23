<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
