<?php

namespace App\Http\Controllers\Api\V1Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

trait Apps
{
    /**
     * GET /api/v1/apps/verify_credentials
     */
    public function getApp(Request $request)
    {
        // FIXME: /api/v1/apps/verify_credentials should be accessible with any
        // valid Access Token, not just a user's access token (i.e., client
        // credentails grant flow access tokens)
        abort_if(! $request->user() || ! $request->user()->token(), 403);

        $client = $request->user()->token()->client;
        $res = [
            'name' => $client->name,
            'website' => null,
            'vapid_key' => null,
        ];

        return $this->json($res);
    }

    /**
     * POST /api/v1/apps
     */
    public function apps(Request $request)
    {
        abort_if(! (bool) config_cache('pixelfed.oauth_enabled'), 404);

        $this->validate($request, [
            'client_name' => 'required',
            'redirect_uris' => 'required',
        ]);

        $uris = collect(explode("\n", $request->redirect_uris))
            ->map('urldecode')
            ->filter()
            ->join(',');

        $secret = Str::random(40);

        $client = new Client;
        $client->forceFill([
            'user_id' => null,
            'name' => e($request->client_name),
            'secret' => $secret,
            'redirect' => $uris,
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ]);
        $client->save();

        $res = [
            'id' => (string) $client->id,
            'name' => $client->name,
            'website' => null,
            'redirect_uri' => $client->redirect,
            'client_id' => (string) $client->id,
            'client_secret' => $secret,
            'vapid_key' => null,
        ];

        return $this->json($res, 200, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
