<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function toProfile()
    {
        return $this->belongsTo(Profile::class, 'to_id');
    }

    public function fromProfile()
    {
        return $this->belongsTo(Profile::class, 'from_id');
    }
}
