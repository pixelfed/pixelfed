<?php

namespace App\Http\Controllers;

use App\Jobs\InboxPipeline\{
	DeleteWorker,
	InboxWorker,
	InboxValidator
};
use App\Jobs\RemoteFollowPipeline\RemoteFollowPipeline;
use App\{
	AccountLog,
	Like,
	Profile,
	Status,
	User
};
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\Site\Nodeinfo;
use App\Util\ActivityPub\{
	Helpers,
	HttpSignature,
	Outbox
};
use Zttp\Zttp;

class FederationController extends Controller
{
	public function nodeinfoWellKnown()
	{
		abort_if(!config('federation.nodeinfo.enabled'), 404);
		return response()->json(Nodeinfo::wellKnown(), 200, [], JSON_UNESCAPED_SLASHES)
			->header('Access-Control-Allow-Origin','*');
	}

	public function nodeinfo()
	{
		abort_if(!config('federation.nodeinfo.enabled'), 404);
		return response()->json(Nodeinfo::get(), 200, [], JSON_UNESCAPED_SLASHES)
			->header('Access-Control-Allow-Origin','*');
	}

	public function webfinger(Request $request)
	{
		if (!config('federation.webfinger.enabled') ||
			!$request->has('resource') ||
			!$request->filled('resource')
		) {
			return response('', 400);
		}

		$resource = $request->input('resource');
		$hash = hash('sha256', $resource);
		$key = 'federation:webfinger:sha256:' . $hash;
		if($cached = Cache::get($key)) {
			return response()->json($cached, 200, [], JSON_UNESCAPED_SLASHES);
		}
		$domain = config('pixelfed.domain.app');
		if(strpos($resource, $domain) == false) {
			return response('', 400);
		}
		$parsed = Nickname::normalizeProfileUrl($resource);
		if(empty($parsed) || $parsed['domain'] !== $domain) {
			return response('', 400);
		}
		$username = $parsed['username'];
		$profile = Profile::whereNull('domain')->whereUsername($username)->first();
		if(!$profile || $profile->status !== null) {
			return response('', 400);
		}
		$webfinger = (new Webfinger($profile))->generate();
		Cache::put($key, $webfinger, 1209600);

		return response()->json($webfinger, 200, [], JSON_UNESCAPED_SLASHES)
			->header('Access-Control-Allow-Origin','*');
	}

	public function hostMeta(Request $request)
	{
		abort_if(!config('federation.webfinger.enabled'), 404);

		$path = route('well-known.webfinger');
		$xml = '<?xml version="1.0" encoding="UTF-8"?><XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0"><Link rel="lrdd" type="application/xrd+xml" template="'.$path.'?resource={uri}"/></XRD>';

		return response($xml)->header('Content-Type', 'application/xrd+xml');
	}

	public function userOutbox(Request $request, $username)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);
		abort_if(!config('federation.activitypub.outbox'), 404);

		// $profile = Profile::whereNull('domain')
		// 	->whereNull('status')
		// 	->whereIsPrivate(false)
		// 	->whereUsername($username)
		// 	->firstOrFail();

		// $key = 'ap:outbox:latest_10:pid:' . $profile->id;
		// $ttl = now()->addMinutes(15);
		// $res = Cache::remember($key, $ttl, function() use($profile) {
		// 	return Outbox::get($profile);
		// });
		$res = [];

		return response(json_encode($res, JSON_UNESCAPED_SLASHES))->header('Content-Type', 'application/activity+json');
	}

	public function userInbox(Request $request, $username)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);
		abort_if(!config('federation.activitypub.inbox'), 404);

		$headers = $request->headers->all();
		$payload = $request->getContent();
		$obj = json_decode($payload, true, 8);

		if(isset($obj['type']) && $obj['type'] === 'Delete') {
			if(!isset($obj['id'])) {
				return;
			}
			usleep(5000);
			$lockKey = 'pf:ap:del-lock:' . hash('sha256', $obj['id']);
			if( isset($obj['actor']) &&
				isset($obj['object']) &&
				isset($obj['id']) &&
				is_string($obj['id']) &&
				is_string($obj['actor']) &&
				is_string($obj['object']) &&
				$obj['actor'] == $obj['object']
			) {
				if(Cache::get($lockKey) !== null) {
					return;
				}
			}
			Cache::put($lockKey, 1, 3600);
			dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');
		} else {
			if(!isset($obj['id'])) {
				return;
			}
			usleep(5000);
			$lockKey = 'pf:ap:user-inbox:activity:' . hash('sha256', $obj['id']);
			if(Cache::get($lockKey) !== null) {
				return;
			}
			Cache::put($lockKey, 1, 3600);
			dispatch(new InboxValidator($username, $headers, $payload))->onQueue('high');
		}
		return;
	}

	public function sharedInbox(Request $request)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);
		abort_if(!config('federation.activitypub.sharedInbox'), 404);

		$headers = $request->headers->all();
		$payload = $request->getContent();
		$obj = json_decode($payload, true, 8);

		if(isset($obj['type']) && $obj['type'] === 'Delete') {
			if(!isset($obj['id'])) {
				return;
			}
			$lockKey = 'pf:ap:del-lock:' . hash('sha256', $obj['id']);
			if( isset($obj['actor']) &&
				isset($obj['object']) &&
				isset($obj['id']) &&
				is_string($obj['id']) &&
				is_string($obj['actor']) &&
				is_string($obj['object']) &&
				$obj['actor'] == $obj['object']
			) {
				if(Cache::get($lockKey) !== null) {
					return;
				}
			}
			Cache::put($lockKey, 1, 3600);
			dispatch(new DeleteWorker($headers, $payload))->onQueue('delete');
		} else {
			dispatch(new InboxWorker($headers, $payload))->onQueue('high');
		}
		return;
	}

	public function userFollowing(Request $request, $username)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);

		$profile = Profile::whereNull('remote_url')
			->whereUsername($username)
			->whereIsPrivate(false)
			->firstOrFail();

		if($profile->status != null) {
			abort(404);
		}

		$obj = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id'       => $request->getUri(),
			'type'     => 'OrderedCollectionPage',
			'totalItems' => 0,
			'orderedItems' => []
		];
		return response()->json($obj);
	}

	public function userFollowers(Request $request, $username)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);

		$profile = Profile::whereNull('remote_url')
			->whereUsername($username)
			->whereIsPrivate(false)
			->firstOrFail();

		if($profile->status != null) {
			abort(404);
		}

		$obj = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id'       => $request->getUri(),
			'type'     => 'OrderedCollectionPage',
			'totalItems' => 0,
			'orderedItems' => []
		];

		return response()->json($obj);
	}
}
