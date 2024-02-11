<?php

namespace App\Models;

use App\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportPost extends Model
{
    use HasFactory;

    protected $casts = [
        'media' => 'array',
        'creation_date' => 'datetime',
        'metadata' => 'json',
    ];

    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }
}
