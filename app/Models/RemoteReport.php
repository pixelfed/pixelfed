<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoteReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status_ids' => 'array',
            'action_taken_meta' => 'array',
            'report_meta' => 'array',
        ];
    }
}
