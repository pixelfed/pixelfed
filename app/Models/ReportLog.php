<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class ReportLog extends Model
{
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
