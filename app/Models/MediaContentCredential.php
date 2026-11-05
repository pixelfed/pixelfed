<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaContentCredential extends Model
{
    public const SOURCE_LOCAL = 'local';

    public const SOURCE_REMOTE = 'remote';

    protected $fillable = [
        'media_id',
        'source',
        'state',
        'signer',
        'generator',
        'signed_at',
        'digital_source_type',
        'ai_label',
        'actions',
        'instance_signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'instance_signed_at' => 'datetime',
            'actions' => 'array',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
