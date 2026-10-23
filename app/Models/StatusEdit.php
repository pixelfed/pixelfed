<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusEdit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ordered_media_attachment_ids' => 'array',
            'media_descriptions' => 'array',
            'poll_options' => 'array',
        ];
    }
}
