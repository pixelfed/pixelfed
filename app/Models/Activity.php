<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Activity extends Model
{
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
