<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomEmojiService;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class AppController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
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

        $uris = implode(',', explode('\n', $request->redirect_uris));

        $client = Passport::client()->forceFill([
            'user_id' => null,
            'name' => e($request->client_name),
            'secret' => str_random(40),
            'redirect' => $uris,
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ]);

        $client->save();

        $res = [
            'id' => $client->id,
            'name' => $client->name,
            'website' => null,
            'redirect_uri' => $client->redirect,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'vapid_key' => null,
        ];

        return $this->json($res, 200, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * GET /api/v1/apps/verify_credentials
     */
    public function getApp(Request $request)
    {
        // FIXME: /api/v1/apps/verify_credentials should be accessible with any
        // valid access token, not just the token that belongs to the app's
        // client_id.
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([
            'name' => $request->user()->token()->client->name,
            'website' => null,
            'vapid_key' => null,
        ]);
    }

    /**
     * GET /api/v1/custom_emojis
     *
     * @return array
     */
    public function customEmojis()
    {
        return response(CustomEmojiService::all())->header('Content-Type', 'application/json');
    }
}