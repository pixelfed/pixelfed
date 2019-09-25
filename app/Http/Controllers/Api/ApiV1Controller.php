<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Jobs\StatusPipeline\StatusDelete;
use Laravel\Passport\Passport;
use Auth, Cache, DB;
use App\{
    Follower,
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

    /**
     * GET /api/v1/accounts/{id}/following
     *
     * @param  integer  $id
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountFollowingById(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $profile = Profile::whereNull('status')->findOrFail($id);

        $settings = $profile->user->settings;
        if($settings->show_profile_following == true) {
            $limit = $request->input('limit') ?? 40;
            $following = $profile->following()->paginate($limit);
            $resource = new Fractal\Resource\Collection($following, new AccountTransformer());
            $res = $this->fractal->createData($resource)->toArray();
        } else {
            $res = [];
        }
        return response()->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}/statuses
     *
     * @param  integer  $id
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function accountStatusesById(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'only_media' => 'nullable',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'limit' => 'nullable|integer|min:1|max:40'
        ]);

        $profile = Profile::whereNull('status')->findOrFail($id);

        $limit = $request->limit ?? 20;
        $max_id = $request->max_id;
        $min_id = $request->min_id;
        $pid = $request->user()->profile_id;
        $scope = $request->only_media == true ? 
            ['photo', 'photo:album', 'video', 'video:album'] :
            ['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];
       
        if($pid == $profile->id) {
            $visibility = ['public', 'unlisted', 'private'];
        } else if($profile->is_private) {
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : [];
        } else {
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];

        }

        $dir = $min_id ? '>' : '<';
        $id = $min_id ?? $max_id;
        $timeline = Status::select(
            'id', 
            'uri',
            'caption',
            'rendered',
            'profile_id', 
            'type',
            'in_reply_to_id',
            'reblog_of_id',
            'is_nsfw',
            'scope',
            'local',
            'created_at',
            'updated_at'
          )->whereProfileId($profile->id)
          ->whereIn('type', $scope)
          ->whereLocal(true)
          ->whereNull('uri')
          ->where('id', $dir, $id)
          ->whereIn('visibility', $visibility)
          ->latest()
          ->limit($limit)
          ->get();

        $resource = new Fractal\Resource\Collection($timeline, new StatusTransformer());
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