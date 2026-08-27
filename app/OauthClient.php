<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthClient extends Model
{
    protected $table = 'oauth_clients';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
