<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class StatusEdit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'ordered_media_attachment_ids' => 'array',
            'media_descriptions' => 'array',
            'poll_options' => 'array',
        ];
    }
}
