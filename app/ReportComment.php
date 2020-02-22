<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
