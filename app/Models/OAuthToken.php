<?php

namespace App\Models;

use Laravel\Passport\Token as PassportToken;

class OAuthToken extends PassportToken
{
    protected $visible = [
        'id',
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'created_at',
        'updated_at',
        'expires_at',
    ];
}
