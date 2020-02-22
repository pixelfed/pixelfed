<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OauthClient extends Model
{
    protected $table = 'oauth_clients';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
