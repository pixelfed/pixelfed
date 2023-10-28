<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Media;
use App\UserSetting;
use App\User;
use Illuminate\Support\Facades\Cache;
use App\Services\AccountService;
use App\Services\BouncerService;
use App\Services\InstanceService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\SearchApiV2Service;
use App\Util\Media\Filter;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\VideoPipeline\{
	VideoOptimize,
	VideoPostProcess,
	VideoThumbnail
};
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Transformer\Api\Mastodon\v1\{
	AccountTransformer,
	MediaTransformer,
	NotificationTransformer,
	StatusTransformer,
};
use App\Transformer\Api\{
	RelationshipTransformer,
};
use App\Util\Site\Nodeinfo;

class ApiV2Controller extends Controller
{
	const PF_API_ENTITY_KEY = "_pe";

	public function json($res, $code = 200, $headers = [])
	{
		return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
	}

    public function instance(Request $request)
    {
		$contact = Cache::remember('api:v1:instance-data:contact', 604800, function () {
			if(config_cache('instance.admin.pid')) {
				return AccountService::getMastodon(config_cache('instance.admin.pid'), true);
			}
			$admin = User::whereIsAdmin(true)->first();
			return $admin && isset($admin->profile_id) ?
				AccountService::getMastodon($admin->profile_id, true) :
				null;
		});

		$rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
			return config_cache('app.rules') ?
				collect(json_decode(config_cache('app.rules'), true))
				->map(function($rule, $key) {
					$id = $key + 1;
					return [
						'id' => "{$id}",
						'text' => $rule
					];
				})
				->toArray() : [];
		});

		$res = [
			'domain' => config('pixelfed.domain.app'),
			'title' => config_cache('app.name'),
			'version' => config('pixelfed.version'),
			'source_url' => 'https://github.com/pixelfed/pixelfed',
			'description' => config_cache('app.short_description'),
			'usage' => [
				'users' => [
					'active_month' => (int) Nodeinfo::activeUsersMonthly()
				]
			],
			'thumbnail' => [
				'url' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
				'blurhash' => InstanceService::headerBlurhash(),
				'versions' => [
					'@1x' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
					'@2x' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg'))
				]
			],
			'languages' => [config('app.locale')],
			'configuration' => [
				'urls' => [
					'streaming' => 'wss://' . config('pixelfed.domain.app'),
					'status' => null
				],
				'accounts' => [
					'max_featured_tags' => 0,
				],
				'statuses' => [
					'max_characters' => (int) config('pixelfed.max_caption_length'),
					'max_media_attachments' => (int) config_cache('pixelfed.max_album_length'),
					'characters_reserved_per_url' => 23
				],
				'media_attachments' => [
					'supported_mime_types' => explode(',', config_cache('pixelfed.media_types')),
					'image_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
					'image_matrix_limit' => 3686400,
					'video_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
					'video_frame_rate_limit' => 240,
					'video_matrix_limit' => 3686400
				],
				'polls' => [
					'max_options' => 4,
					'max_characters_per_option' => 50,
					'min_expiration' => 300,
					'max_expiration' => 2629746,
				],
				'translation' => [
					'enabled' => false,
				],
			],
			'registrations' => [
				'enabled' => (bool) config_cache('pixelfed.open_registration'),
				'approval_required' => false,
				'message' => null
			],
			'contact' => [
				'email' => config('instance.email'),
				'account' => $contact
			],
			'rules' => $rules
		];

    	return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
    }

	/**
	 * GET /api/v2/search
	 *
	 *
	 * @return array
	 */
	public function search(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'q' => 'required|string|min:1|max:100',
			'account_id' => 'nullable|string',
			'max_id' => 'nullable|string',
			'min_id' => 'nullable|string',
			'type' => 'nullable|in:accounts,hashtags,statuses',
			'exclude_unreviewed' => 'nullable',
			'resolve' => 'nullable',
			'limit' => 'nullable|integer|max:40',
			'offset' => 'nullable|integer',
			'following' => 'nullable'
		]);

		$mastodonMode = !$request->has('_pe');
		return $this->json(SearchApiV2Service::query($request, $mastodonMode));
	}

	/**
	 * GET /api/v2/streaming/config
	 *
	 *
	 * @return object
	 */
	public function getWebsocketConfig()
	{
		return config('broadcasting.default') === 'pusher' ? [
			'host' => config('broadcasting.connections.pusher.options.host'),
			'port' => config('broadcasting.connections.pusher.options.port'),
			'key' => config('broadcasting.connections.pusher.key'),
			'cluster' => config('broadcasting.connections.pusher.options.cluster')
		] : [];
	}

	/**
	 * POST /api/v2/media
	 *
	 *
	 * @return MediaTransformer
	 */
	public function mediaUploadV2(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
		  	'file.*' => [
				'required_without:file',
				'mimetypes:' . config_cache('pixelfed.media_types'),
				'max:' . config_cache('pixelfed.max_photo_size'),
			],
			'file' => [
				'required_without:file.*',
				'mimetypes:' . config_cache('pixelfed.media_types'),
				'max:' . config_cache('pixelfed.max_photo_size'),
			],
		  'filter_name' => 'nullable|string|max:24',
		  'filter_class' => 'nullable|alpha_dash|max:24',
		  'description' => 'nullable|string|max:' . config_cache('pixelfed.max_altext_length'),
		  'replace_id' => 'sometimes'
		]);

		$user = $request->user();

		if($user->last_active_at == null) {
			return [];
		}

		if(empty($request->file('file'))) {
			return response('', 422);
		}

		$limitKey = 'compose:rate-limit:media-upload:' . $user->id;
		$limitTtl = now()->addMinutes(15);
		$limitReached = Cache::remember($limitKey, $limitTtl, function() use($user) {
			$dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

			return $dailyLimit >= 1250;
		});
		abort_if($limitReached == true, 429);

		$profile = $user->profile;

		if(config_cache('pixelfed.enforce_account_limit') == true) {
			$size = Cache::remember($user->storageUsedKey(), now()->addDays(3), function() use($user) {
				return Media::whereUserId($user->id)->sum('size') / 1000;
			});
			$limit = (int) config_cache('pixelfed.max_account_size');
			if ($size >= $limit) {
			   abort(403, 'Account size limit reached.');
			}
		}

		$filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
		$filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;

		$photo = $request->file('file');

		$mimes = explode(',', config_cache('pixelfed.media_types'));
		if(in_array($photo->getMimeType(), $mimes) == false) {
			abort(403, 'Invalid or unsupported mime type.');
		}

		$storagePath = MediaPathService::get($user, 2);
		$path = $photo->storePublicly($storagePath);
		$hash = \hash_file('sha256', $photo);
		$license = null;
		$mime = $photo->getMimeType();

		$settings = UserSetting::whereUserId($user->id)->first();

		if($settings && !empty($settings->compose_settings)) {
			$compose = $settings->compose_settings;

			if(isset($compose['default_license']) && $compose['default_license'] != 1) {
				$license = $compose['default_license'];
			}
		}

		abort_if(MediaBlocklistService::exists($hash) == true, 451);

		if($request->has('replace_id')) {
			$rpid = $request->input('replace_id');
			$removeMedia = Media::whereNull('status_id')
				->whereUserId($user->id)
				->whereProfileId($profile->id)
				->where('created_at', '>', now()->subHours(2))
				->find($rpid);
			if($removeMedia) {
				MediaDeletePipeline::dispatch($removeMedia)
					->onQueue('mmo')
					->delay(now()->addMinutes(15));
			}
		}

		$media = new Media();
		$media->status_id = null;
		$media->profile_id = $profile->id;
		$media->user_id = $user->id;
		$media->media_path = $path;
		$media->original_sha256 = $hash;
		$media->size = $photo->getSize();
		$media->mime = $mime;
		$media->caption = $request->input('description');
		$media->filter_class = $filterClass;
		$media->filter_name = $filterName;
		if($license) {
			$media->license = $license;
		}
		$media->save();

		switch ($media->mime) {
			case 'image/jpeg':
			case 'image/png':
				ImageOptimize::dispatch($media)->onQueue('mmo');
				break;

			case 'video/mp4':
				VideoThumbnail::dispatch($media)->onQueue('mmo');
				$preview_url = '/storage/no-preview.png';
				$url = '/storage/no-preview.png';
				break;
		}

		Cache::forget($limitKey);
		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($media, new MediaTransformer());
		$res = $fractal->createData($resource)->toArray();
		$res['preview_url'] = $media->url(). '?v=' . time();
		$res['url'] = null;
		return $this->json($res, 202);
	}
}
