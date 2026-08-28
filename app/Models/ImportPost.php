<?php

namespace App\Models;

use App\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImportPost extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'creation_date' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function status(): HasOne
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
}
