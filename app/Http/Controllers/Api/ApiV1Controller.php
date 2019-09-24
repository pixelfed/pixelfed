<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Jobs\StatusPipeline\StatusDelete;
use Laravel\Passport\Passport;
use Auth, Cache, DB;
use App\{
    Like,
    Media,
    Profile,
    Status
};
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    RelationshipTransformer,
    StatusTransformer,
};
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

use App\Services\NotificationService;

class ApiV1Controller extends Controller 
{
	protected $fractal;

	public function __construct()
	{
		$this->fractal = new Fractal\Manager();
		$this->fractal->setSerializer(new ArraySerializer());
	}
	public function apps(Request $request)
	{
		abort_if(!config('pixelfed.oauth_enabled'), 404);

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

	public function accountById(Request $request, $id)
	{
		$profile = Profile::whereNull('status')->findOrFail($id);
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

    public function statusById(Request $request, $id)
    {
        $status = Status::whereVisibility('public')->findOrFail($id);
        $resource = new Fractal\Resource\Item($status, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function instance(Request $request)
    {
        $res = [
            'description' => 'Pixelfed - Photo sharing for everyone',
            'email' => config('instance.email'),
            'languages' => ['en'],
            'max_toot_chars' => config('pixelfed.max_caption_length'),
            'registrations' => config('pixelfed.open_registration'),
            'stats' => [
                'user_count' => 0,
                'status_count' => 0,
                'domain_count' => 0
            ],
            'thumbnail' => config('app.url') . '/img/pixelfed-icon-color.png',
            'title' => 'Pixelfed (' . config('pixelfed.domain.app') . ')',
            'uri' => config('app.url'),
            'urls' => [],
            'version' => '2.7.2 (compatible; Pixelfed ' . config('pixelfed.version') . ')'
        ];
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function filters(Request $request)
    {
        // Pixelfed does not yet support keyword filters
        return response()->json([]);
    }

    public function context(Request $request)
    {
        // todo
        $res = [
            'ancestors' => [],
            'descendants' => []
        ];

        return response()->json($res);
    }
}