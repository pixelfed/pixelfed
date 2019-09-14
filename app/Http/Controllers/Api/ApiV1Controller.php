<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Jobs\StatusPipeline\StatusDelete;
use Laravel\Passport\Passport;
use Auth, Cache, DB;
use Carbon\Carbon;
use App\{
    Like,
    Media,
    Profile,
    Status
};

use App\Services\NotificationService;

class ApiV1Controller extends Controller {

	public function apps(Request $request)
	{
		$this->validate($request, [
			'client_name' 		=> 'required',
			'redirect_uris' 	=> 'required',
			'scopes' 			=> 'nullable',
			'website' 			=> 'nullable'
		]);

        $client = Passport::client()->forceFill([
            'user_id' => null,
            'name' => e($request->client_name),
            'secret' => Str::random(40),
            'redirect' => $request->redirect_uris,
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
        	'vapid_key' => null
        ];
        return $res;
	}
}