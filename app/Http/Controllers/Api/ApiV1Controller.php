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

    /**
     * GET /api/v1/accounts/{id}
     *
     * @param  integer  $id
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
	public function accountById(Request $request, $id)
	{
		$profile = Profile::whereNull('status')->findOrFail($id);
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

    /**
     * PATCH /api/v1/accounts/update_credentials
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountUpdateCredentials(Request, $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'display_name'      => 'nullable|string',
            'note'              => 'nullable|string',
            'locked'            => 'nullable|boolean',
            // 'source.privacy'    => 'nullable|in:unlisted,public,private',
            // 'source.sensitive'  => 'nullable|boolean'
        ]);

        $user = $request->user();
        $profile = $user->profile;

        $displayName = $request->input('display_name');
        $note = $request->input('note');
        $locked = $request->input('locked');
        // $privacy = $request->input('source.privacy');
        // $sensitive = $request->input('source.sensitive');

        $changes = false;

        if($displayName !== $user->name) {
            $user->name = $displayName;
            $profile->name = $displayName;
            $changes = true;
        }

        if($note !== $profile->bio) {
            $profile->bio = e($note);
            $changes = true;
        }

        if(!is_null($locked)) {
            $profile->is_private = $locked;
            $changes = true;
        }

        if($changes) {
            $user->save();
            $profile->save()
        }

        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}/followers
     *
     * @param  integer  $id
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountFollowersById(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $profile = Profile::whereNull('status')->findOrFail($id);

        $settings = $profile->user->settings;
        if($settings->show_profile_followers == true) {
            $limit = $request->input('limit') ?? 40;
            $followers = $profile->followers()->paginate($limit);
            $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
            $res = $this->fractal->createData($resource)->toArray();
        } else {
            $res = [];
        }
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

    public function createStatus(Request $request)
    {
        abort_if(!$request->user(), 403);
        
        $this->validate($request, [
            'status' => 'string',
            'media_ids' => 'array',
            'media_ids.*' => 'integer|min:1',
            'sensitive' => 'nullable|boolean',
            'visibility' => 'string|in:private,unlisted,public',
            'in_reply_to_id' => 'integer'
        ]);

        if(!$request->filled('media_ids') && !$request->filled('in_reply_to_id')) {
            abort(403, 'Empty statuses are not allowed');
        }
    }
}