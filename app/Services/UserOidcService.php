<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;

class UserOidcService extends GenericProvider
{
    public static function build()
    {
        return new UserOidcService([
            'clientId' => config('remote-auth.oidc.clientId'),
            'clientSecret' => config('remote-auth.oidc.clientSecret'),
            'redirectUri' => url('auth/oidc/callback'),
            'urlAuthorize' => config('remote-auth.oidc.authorizeURL'),
            'urlAccessToken' => config('remote-auth.oidc.tokenURL'),
            'urlResourceOwnerDetails' => config('remote-auth.oidc.profileURL'),
            'scopes' => config('remote-auth.oidc.scopes'),
            'responseResourceOwnerId' => config('remote-auth.oidc.field_id'),
        ]);
    }
}
