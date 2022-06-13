<?php

namespace App\Util\ActivityPub;

use DB, Cache, Purify, Storage, Request, Validator;
use App\{
	Activity,
	Follower,
	Instance,
	Like,
	Media,
	Notification,
	Profile,
	Status
};
use Zttp\Zttp;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Validation\Rule;
use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Jobs\RemoteFollowPipeline\RemoteFollowImportRecent;
use App\Jobs\ImageOptimizePipeline\{ImageOptimize,ImageThumbnail};
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Jobs\StatusPipeline\StatusTagsPipeline;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Support\Str;
use App\Services\ActivityPubFetchService;
use App\Services\ActivityPubDeliveryService;
use App\Services\CustomEmojiService;
use App\Services\InstanceService;
use App\Services\MediaPathService;
use App\Services\MediaStorageService;
use App\Services\NetworkTimelineService;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Jobs\AvatarPipeline\RemoteAvatarFetch;
use App\Util\Media\License;
use App\Models\Poll;
use Illuminate\Contracts\Cache\LockTimeoutException;

class Helpers {

	public static function validateObject($data)
	{
		$verbs = ['Create', 'Announce', 'Like', 'Follow', 'Delete', 'Accept', 'Reject', 'Undo', 'Tombstone'];

		$valid = Validator::make($data, [
			'type' => [
				'required',
				'string',
				Rule::in($verbs)
			],
			'id' => 'required|string',
			'actor' => 'required|string|url',
			'object' => 'required',
			'object.type' => 'required_if:type,Create',
			'object.attributedTo' => 'required_if:type,Create|url',
			'published' => 'required_if:type,Create|date'
		])->passes();

		return $valid;
	}

	public static function verifyAttachments($data)
	{
		if(!isset($data['object']) || empty($data['object'])) {
			$data = ['object'=>$data];
		}

		$activity = $data['object'];

		$mimeTypes = explode(',', config_cache('pixelfed.media_types'));
		$mediaTypes = in_array('video/mp4', $mimeTypes) ? ['Document', 'Image', 'Video'] : ['Document', 'Image'];

		// Peertube
		// $mediaTypes = in_array('video/mp4', $mimeTypes) ? ['Document', 'Image', 'Video', 'Link'] : ['Document', 'Image'];

		if(!isset($activity['attachment']) || empty($activity['attachment'])) {
			return false;
		}

		// peertube
		// $attachment = is_array($activity['url']) ?
		// 	collect($activity['url'])
		// 	->filter(function($media) {
		// 		return $media['type'] == 'Link' && $media['mediaType'] == 'video/mp4';
		// 	})
		// 	->take(1)
		// 	->values()
		// 	->toArray()[0] : $activity['attachment'];

		$attachment = $activity['attachment'];

		$valid = Validator::make($attachment, [
			'*.type' => [
				'required',
				'string',
				Rule::in($mediaTypes)
			],
			'*.url' => 'required|url|max:255',
			'*.mediaType'  => [
				'required',
				'string',
				Rule::in($mimeTypes)
			],
			'*.name' => 'sometimes|nullable|string|max:255'
		])->passes();

		return $valid;
	}

	public static function normalizeAudience($data, $localOnly = true)
	{
		if(!isset($data['to'])) {
			return;
		}

		$audience = [];
		$audience['to'] = [];
		$audience['cc'] = [];
		$scope = 'private';

		if(is_array($data['to']) && !empty($data['to'])) {
			foreach ($data['to'] as $to) {
				if($to == 'https://www.w3.org/ns/activitystreams#Public') {
					$scope = 'public';
					continue;
				}
				$url = $localOnly ? self::validateLocalUrl($to) : self::validateUrl($to);
				if($url != false) {
					array_push($audience['to'], $url);
				}
			}
		}

		if(is_array($data['cc']) && !empty($data['cc'])) {
			foreach ($data['cc'] as $cc) {
				if($cc == 'https://www.w3.org/ns/activitystreams#Public') {
					$scope = 'unlisted';
					continue;
				}
				$url = $localOnly ? self::validateLocalUrl($cc) : self::validateUrl($cc);
				if($url != false) {
					array_push($audience['cc'], $url);
				}
			}
		}
		$audience['scope'] = $scope;
		return $audience;
	}

	public static function userInAudience($profile, $data)
	{
		$audience = self::normalizeAudience($data);
		$url = $profile->permalink();
		return in_array($url, $audience['to']) || in_array($url, $audience['cc']);
	}

	public static function validateUrl($url)
	{
		if(is_array($url)) {
			$url = $url[0];
		}

		$hash = hash('sha256', $url);
		$key = "helpers:url:valid:sha256-{$hash}";
		$ttl = now()->addMinutes(5);

		$valid = Cache::remember($key, $ttl, function() use($url) {
			$localhosts = [
				'127.0.0.1', 'localhost', '::1'
			];

			if(mb_substr($url, 0, 8) !== 'https://') {
				return false;
			}

			$valid = filter_var($url, FILTER_VALIDATE_URL);

			if(!$valid) {
				return false;
			}

			$host = parse_url($valid, PHP_URL_HOST);

			// if(count(dns_get_record($host, DNS_A | DNS_AAAA)) == 0) {
			// 	return false;
			// }

			if(config('costar.enabled') == true) {
				if(
					(config('costar.domain.block') != null && Str::contains($host, config('costar.domain.block')) == true) ||
					(config('costar.actor.block') != null && in_array($url, config('costar.actor.block')) == true)
				) {
					return false;
				}
			}

			if(app()->environment() === 'production') {
				$bannedInstances = InstanceService::getBannedDomains();
				if(in_array($host, $bannedInstances)) {
					return false;
				}
			}


			if(in_array($host, $localhosts)) {
				return false;
			}

			return $url;
		});

		return $valid;
	}

	public static function validateLocalUrl($url)
	{
		$url = self::validateUrl($url);
		if($url == true) {
			$domain = config('pixelfed.domain.app');
			$host = parse_url($url, PHP_URL_HOST);
			$url = $domain === $host ? $url : false;
			return $url;
		}
		return false;
	}

	public static function zttpUserAgent()
	{
		$version = config('pixelfed.version');
		$url = config('app.url');
		return [
			'Accept'     => 'application/activity+json',
			'User-Agent' => "(Pixelfed/{$version}; +{$url})",
		];
	}

	public static function fetchFromUrl($url = false)
	{
		if(self::validateUrl($url) == false) {
			return;
		}

		$hash = hash('sha256', $url);
		$key = "helpers:url:fetcher:sha256-{$hash}";
		$ttl = now()->addMinutes(5);

		return Cache::remember($key, $ttl, function() use($url) {
			$res = ActivityPubFetchService::get($url);
			$res = json_decode($res, true, 8);
			if(json_last_error() == JSON_ERROR_NONE) {
				return $res;
			} else {
				return false;
			}
		});
	}

	public static function fetchProfileFromUrl($url)
	{
		return self::fetchFromUrl($url);
	}

	public static function pluckval($val)
	{
		if(is_string($val)) {
			return $val;
		}

		if(is_array($val)) {
			return !empty($val) ? $val[0] : null;
		}

		return null;
	}

	public static function statusFirstOrFetch($url, $replyTo = false)
	{
		$url = self::validateUrl($url);
		if($url == false) {
			return;
		}

		$host = parse_url($url, PHP_URL_HOST);
		$local = config('pixelfed.domain.app') == $host ? true : false;

		if($local) {
			$id = (int) last(explode('/', $url));
			return Status::whereNotIn('scope', ['draft','archived'])->findOrFail($id);
		}

		$cached = Status::whereNotIn('scope', ['draft','archived'])
			->whereUri($url)
			->orWhere('object_url', $url)
			->first();

		if($cached) {
			return $cached;
		}

		$res = self::fetchFromUrl($url);

		if(!$res || empty($res) || isset($res['error']) || !isset($res['@context']) ) {
			return;
		}

		if(isset($res['object'])) {
			$activity = $res;
		} else {
			$activity = ['object' => $res];
		}

		$scope = 'private';

		$cw = isset($res['sensitive']) ? (bool) $res['sensitive'] : false;

		if(isset($res['to']) == true) {
			if(is_array($res['to']) && in_array('https://www.w3.org/ns/activitystreams#Public', $res['to'])) {
				$scope = 'public';
			}
			if(is_string($res['to']) && 'https://www.w3.org/ns/activitystreams#Public' == $res['to']) {
				$scope = 'public';
			}
		}

		if(isset($res['cc']) == true) {
			if(is_array($res['cc']) && in_array('https://www.w3.org/ns/activitystreams#Public', $res['cc'])) {
				$scope = 'unlisted';
			}
			if(is_string($res['cc']) && 'https://www.w3.org/ns/activitystreams#Public' == $res['cc']) {
				$scope = 'unlisted';
			}
		}

		if(config('costar.enabled') == true) {
			$blockedKeywords = config('costar.keyword.block');
			if($blockedKeywords !== null) {
				$keywords = config('costar.keyword.block');
				foreach($keywords as $kw) {
					if(Str::contains($res['content'], $kw) == true) {
						return;
					}
				}
			}

			$unlisted = config('costar.domain.unlisted');
			if(in_array(parse_url($url, PHP_URL_HOST), $unlisted) == true) {
				$unlisted = true;
				$scope = 'unlisted';
			} else {
				$unlisted = false;
			}

			$cwDomains = config('costar.domain.cw');
			if(in_array(parse_url($url, PHP_URL_HOST), $cwDomains) == true) {
				$cw = true;
			}
		}

		$id = isset($res['id']) ? self::pluckval($res['id']) : self::pluckval($url);
		$idDomain = parse_url($id, PHP_URL_HOST);
		$urlDomain = parse_url($url, PHP_URL_HOST);

		if(!self::validateUrl($id)) {
			return;
		}

		$attributedTo = is_string($activity['object']['attributedTo']) ?
			$activity['object']['attributedTo'] :
			(is_array($activity['object']['attributedTo']) ?
				collect($activity['object']['attributedTo'])
					->filter(function($o) {
						return $o && isset($o['type']) && $o['type'] == 'Person';
					})
					->pluck('id')
					->first() : null
			);

		if($attributedTo) {
			$actorDomain = parse_url($attributedTo, PHP_URL_HOST);
			if(!self::validateUrl($attributedTo) ||
				$idDomain !== $actorDomain ||
				$actorDomain !== $urlDomain
			)
			{
				return;
			}
		}

		if($idDomain !== $urlDomain) {
			return;
		}

		$profile = self::profileFirstOrNew($attributedTo);
		if(isset($activity['object']['inReplyTo']) && !empty($activity['object']['inReplyTo']) || $replyTo == true) {
			$reply_to = self::statusFirstOrFetch(self::pluckval($activity['object']['inReplyTo']), false);
			$reply_to = optional($reply_to)->id;
		} else {
			$reply_to = null;
		}
		$ts = self::pluckval($res['published']);

		if($scope == 'public' && in_array($urlDomain, InstanceService::getUnlistedDomains())) {
			$scope = 'unlisted';
		}

		if(in_array($urlDomain, InstanceService::getNsfwDomains())) {
			$cw = true;
		}

		$statusLockKey = 'helpers:status-lock:' . hash('sha256', $res['id']);
		$status = Cache::lock($statusLockKey)
			->get(function () use(
				$profile,
				$res,
				$url,
				$ts,
				$reply_to,
				$cw,
				$scope,
				$id
		) {

			if($res['type'] === 'Question') {
				$status = self::storePoll(
					$profile,
					$res,
					$url,
					$ts,
					$reply_to,
					$cw,
					$scope,
					$id
				);
				return $status;
			}

			return self::storeStatus($url, $profile, $res);
		});

		return $status;
	}

	public static function storeStatus($url, $profile, $activity)
	{
		$id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($activity['url']);
		$url = isset($activity['url']) && is_string($activity['url']) ? $activity['url'] : $id;
		$idDomain = parse_url($id, PHP_URL_HOST);
		$urlDomain = parse_url($url, PHP_URL_HOST);
		if(!self::validateUrl($id) || !self::validateUrl($url)) {
			return;
		}

		$reply_to = self::getReplyTo($activity);

		return DB::transaction(function() use($url, $profile, $activity, $reply_to, $id) {
			$ts = self::pluckval($activity['published']);
			$scope = self::getScope($activity, $url);
			$cw = self::getSensitive($activity, $url);
			$pid = is_object($profile) ? $profile->id : (is_array($profile) ? $profile['id'] : null);

			if(!$pid) {
				return;
			}

			$status = new Status;
			$status->profile_id = $pid;
			$status->url = $url;
			$status->uri = $url;
			$status->object_url = $id;
			$status->caption = strip_tags($activity['content']);
			$status->rendered = Purify::clean($activity['content']);
			$status->created_at = Carbon::parse($ts)->tz('UTC');
			$status->in_reply_to_id = $reply_to;
			$status->local = false;
			$status->is_nsfw = $cw;
			$status->scope = $scope;
			$status->visibility = $scope;
			$status->cw_summary = $cw == true && isset($activity['summary']) ?
				Purify::clean(strip_tags($activity['summary'])) : null;
			$status->save();

			if($reply_to == null) {
				self::importNoteAttachment($activity, $status);
			} else {
				StatusReplyPipeline::dispatch($status);
			}

			if(isset($activity['tag']) && is_array($activity['tag']) && !empty($activity['tag'])) {
				StatusTagsPipeline::dispatch($activity, $status);
			}

			if( config('instance.timeline.network.cached') &&
				$status->in_reply_to_id === null &&
				$status->reblog_of_id === null &&
				in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album']) &&
				$status->created_at->gt(now()->subHours(config('instance.timeline.network.max_hours_old')))
			) {
				NetworkTimelineService::add($status->id);
			}

			return $status;
		});
	}

	public static function getSensitive($activity, $url)
	{
		$id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
		$url = isset($activity['url']) ? self::pluckval($activity['url']) : $id;
		$urlDomain = parse_url($url, PHP_URL_HOST);

		$cw = isset($activity['sensitive']) ? (bool) $activity['sensitive'] : false;

		if(in_array($urlDomain, InstanceService::getNsfwDomains())) {
			$cw = true;
		}

		return $cw;
	}

	public static function getReplyTo($activity)
	{
		$reply_to = null;
		$inReplyTo = isset($activity['inReplyTo']) && !empty($activity['inReplyTo']) ?
			self::pluckval($activity['inReplyTo']) :
			false;

		if($inReplyTo) {
			$reply_to = self::statusFirstOrFetch($inReplyTo);
			if($reply_to) {
				$reply_to = optional($reply_to)->id;
			}
		} else {
			$reply_to = null;
		}

		return $reply_to;
	}

	public static function getScope($activity, $url)
	{
		$id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
		$url = isset($activity['url']) ? self::pluckval($activity['url']) : $id;
		$urlDomain = parse_url($url, PHP_URL_HOST);
		$scope = 'private';

		if(isset($activity['to']) == true) {
			if(is_array($activity['to']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['to'])) {
				$scope = 'public';
			}
			if(is_string($activity['to']) && 'https://www.w3.org/ns/activitystreams#Public' == $activity['to']) {
				$scope = 'public';
			}
		}

		if(isset($activity['cc']) == true) {
			if(is_array($activity['cc']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['cc'])) {
				$scope = 'unlisted';
			}
			if(is_string($activity['cc']) && 'https://www.w3.org/ns/activitystreams#Public' == $activity['cc']) {
				$scope = 'unlisted';
			}
		}

		if($scope == 'public' && in_array($urlDomain, InstanceService::getUnlistedDomains())) {
			$scope = 'unlisted';
		}

		return $scope;
	}

	private static function storePoll($profile, $res, $url, $ts, $reply_to, $cw, $scope, $id)
	{
		if(!isset($res['endTime']) || !isset($res['oneOf']) || !is_array($res['oneOf']) || count($res['oneOf']) > 4) {
			return;
		}

		$options = collect($res['oneOf'])->map(function($option) {
			return $option['name'];
		})->toArray();

		$cachedTallies = collect($res['oneOf'])->map(function($option) {
			return $option['replies']['totalItems'] ?? 0;
		})->toArray();

		$status = new Status;
		$status->profile_id = $profile->id;
		$status->url = isset($res['url']) ? $res['url'] : $url;
		$status->uri = isset($res['url']) ? $res['url'] : $url;
		$status->object_url = $id;
		$status->caption = strip_tags($res['content']);
		$status->rendered = Purify::clean($res['content']);
		$status->created_at = Carbon::parse($ts)->tz('UTC');
		$status->in_reply_to_id = null;
		$status->local = false;
		$status->is_nsfw = $cw;
		$status->scope = 'draft';
		$status->visibility = 'draft';
		$status->cw_summary = $cw == true && isset($res['summary']) ?
			Purify::clean(strip_tags($res['summary'])) : null;
		$status->save();

		$poll = new Poll;
		$poll->status_id = $status->id;
		$poll->profile_id = $status->profile_id;
		$poll->poll_options = $options;
		$poll->cached_tallies = $cachedTallies;
		$poll->votes_count = array_sum($cachedTallies);
		$poll->expires_at = now()->parse($res['endTime']);
		$poll->last_fetched_at = now();
		$poll->save();

		$status->type = 'poll';
		$status->scope = $scope;
		$status->visibility = $scope;
		$status->save();

		return $status;
	}

	public static function statusFetch($url)
	{
		return self::statusFirstOrFetch($url);
	}

	public static function importNoteAttachment($data, Status $status)
	{
		if(self::verifyAttachments($data) == false) {
			// \Log::info('importNoteAttachment::failedVerification.', [$data['id']]);
			$status->viewType();
			return;
		}
		$attachments = isset($data['object']) ? $data['object']['attachment'] : $data['attachment'];
		// peertube
		// if(!$attachments) {
		// 	$obj = isset($data['object']) ? $data['object'] : $data;
		// 	$attachments = is_array($obj['url']) ? $obj['url'] : null;
		// }
		$user = $status->profile;
		$storagePath = MediaPathService::get($user, 2);
		$allowed = explode(',', config_cache('pixelfed.media_types'));

		foreach($attachments as $media) {
			$type = $media['mediaType'];
			$url = $media['url'];
			$blurhash = isset($media['blurhash']) ? $media['blurhash'] : null;
			$license = isset($media['license']) ? License::nameToId($media['license']) : null;
			$valid = self::validateUrl($url);
			if(in_array($type, $allowed) == false || $valid == false) {
				continue;
			}

			$media = new Media();
			$media->blurhash = $blurhash;
			$media->remote_media = true;
			$media->status_id = $status->id;
			$media->profile_id = $status->profile_id;
			$media->user_id = null;
			$media->media_path = $url;
			$media->remote_url = $url;
			if($license) {
				$media->license = $license;
			}
			$media->mime = $type;
			$media->version = 3;
			$media->save();

			if(config_cache('pixelfed.cloud_storage') == true) {
				MediaStoragePipeline::dispatch($media);
			}
		}

		$status->viewType();
		return;
	}

	public static function profileFirstOrNew($url)
	{
		$url = self::validateUrl($url);
		if($url == false) {
			return;
		}

		$host = parse_url($url, PHP_URL_HOST);
		$local = config('pixelfed.domain.app') == $host ? true : false;

		if($local == true) {
			$id = last(explode('/', $url));
			return Profile::whereNull('status')
				->whereNull('domain')
				->whereUsername($id)
				->firstOrFail();
		}

		if($profile = Profile::whereRemoteUrl($url)->first()) {
			if($profile->last_fetched_at->lt(now()->subHours(24))) {
				return self::profileUpdateOrCreate($url);
			}
			return $profile;
		}

		return self::profileUpdateOrCreate($url);
	}

	public static function profileUpdateOrCreate($url)
	{
		$hash = base64_encode($url);
		$key = 'ap:profile:by_url:' . $hash;
		$lock = Cache::lock($key, 30);
		$profile = null;

		try {
			$lock->block(5);

			$res = self::fetchProfileFromUrl($url);
			if(isset($res['id']) == false) {
				return;
			}
			$domain = parse_url($res['id'], PHP_URL_HOST);
			if(!isset($res['preferredUsername']) && !isset($res['nickname'])) {
				return;
			}
			$username = (string) Purify::clean($res['preferredUsername'] ?? $res['nickname']);
			if(empty($username)) {
				return;
			}
			$remoteUsername = $username;
			$webfinger = "@{$username}@{$domain}";

			abort_if(!self::validateUrl($res['inbox']), 400);
			abort_if(!self::validateUrl($res['id']), 400);

			$profile = DB::transaction(function() use($domain, $webfinger, $res) {
				$instance = Instance::updateOrCreate([
					'domain' => $domain
				]);
				if($instance->wasRecentlyCreated == true) {
					\App\Jobs\InstancePipeline\FetchNodeinfoPipeline::dispatch($instance)->onQueue('low');
				}

				$profile = Profile::updateOrCreate(
					[
						'domain' => strtolower($domain),
						'username' => Purify::clean($webfinger),
						'remote_url' => $res['id'],
					],
					[
						'name' => isset($res['name']) ? Purify::clean($res['name']) : 'user',
						'bio' => isset($res['summary']) ? Purify::clean($res['summary']) : null,
						'sharedInbox' => isset($res['endpoints']) && isset($res['endpoints']['sharedInbox']) ? $res['endpoints']['sharedInbox'] : null,
						'inbox_url' => $res['inbox'],
						'outbox_url' => isset($res['outbox']) ? $res['outbox'] : null,
						'public_key' => $res['publicKey']['publicKeyPem'],
						'key_id' => $res['publicKey']['id'],
						'webfinger' => Purify::clean($webfinger),
					]
				);

				if( $profile->last_fetched_at == null ||
					$profile->last_fetched_at->lt(now()->subHours(24))
				) {
					RemoteAvatarFetch::dispatch($profile);
				}
				$profile->last_fetched_at = now();
				$profile->save();
				return $profile;
			});

			return $profile;
		} catch (LockTimeoutException $e) {
		} finally {
		    optional($lock)->release();
		}

		return $profile;
	}

	public static function profileFetch($url)
	{
		return self::profileFirstOrNew($url);
	}

	public static function sendSignedObject($profile, $url, $body)
	{
		ActivityPubDeliveryService::queue()
			->from($profile)
			->to($url)
			->payload($body)
			->send();
	}
}
