<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VinylHubStatusOperation extends Model
{
    public const STATE_ACCEPTED = 'accepted';

    public const STATE_INCOMPLETE = 'incomplete';

    protected $guarded = [];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class)->withTrashed();
    }
}
