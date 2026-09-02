<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oauth_clients')]
class OauthClient extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
