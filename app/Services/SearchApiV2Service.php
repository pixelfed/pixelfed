<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\{Hashtag, Profile, Status};
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\StatusTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Str;
use App\Services\AccountService;
use App\Services\HashtagService;
use App\Services\StatusService;

class SearchApiV2Service
{
	private $query;
	static $mastodonMode = false;

	public static function query($query, $mastodonMode = false)
	{
		self::$mastodonMode = $mastodonMode;
		return (new self)->run($query);
	}

	protected function run($query)
	{
		$this->query = $query;
		$q = urldecode($query->input('q'));

		if($query->has('resolve') &&
			$query->resolve == true &&
			( Str::startsWith($q, 'https://') ||
			  Str::substrCount($q, '@') == 2)
		) {
			return $this->resolveQuery();
		}

		if($query->has('type')) {
			switch ($query->input('type')) {
				case 'accounts':
					return [
						'accounts' => $this->accounts(),
						'hashtags' => [],
						'statuses' => []
					];
                case 'hashtags':
					return [
						'accounts' => [],
						'hashtags' => $this->hashtags(),
						'statuses' => []
					];
                case 'statuses':
					return [
						'accounts' => [],
						'hashtags' => [],
						'statuses' => $this->statuses()
					];
            }
		}

		if($query->has('account_id')) {
			return [
				'accounts' => [],
				'hashtags' => [],
				'statuses' => $this->statusesById()
			];
		}

		return [
			'accounts' => $this->accounts(),
			'hashtags' => $this->hashtags(),
			'statuses' => $this->statuses()
		];
	}

	protected function accounts()
	{
		$mastodonMode = self::$mastodonMode;
		$user = request()->user();
		$limit = $this->query->input('limit') ?? 20;
		$offset = $this->query->input('offset') ?? 0;
		$query = '%' . $this->query->input('q') . '%';
		$banned = InstanceService::getBannedDomains();
		$results = Profile::select('profiles.*', 'followers.profile_id', 'followers.created_at')
			->whereNull('status')
			->leftJoin('followers', function($join) use($user) {
				return $join->on('profiles.id', '=', 'followers.following_id')
					->where('followers.profile_id', $user->profile_id);
			})
			->where('username', 'like', $query)
			->orderBy('domain')
			->orderByDesc('profiles.followers_count')
			->orderByDesc('followers.created_at')
			->offset($offset)
			->limit($limit)
			->get()
			->filter(function($profile) use ($banned) {
				return in_array($profile->domain, $banned) == false;
			})
			->map(function($res) use($mastodonMode) {
				return $mastodonMode ?
				AccountService::getMastodon($res['id']) :
				AccountService::get($res['id']);
			})
			->filter(function($account) {
				return $account && isset($account['id']);
			})
			->values();

		return $results;
	}

	protected function hashtags()
	{
		$mastodonMode = self::$mastodonMode;
		$limit = $this->query->input('limit') ?? 20;
		$offset = $this->query->input('offset') ?? 0;
		$query = '%' . $this->query->input('q') . '%';
		return Hashtag::whereIsBanned(false)
			->where('name', 'like', $query)
			->offset($offset)
			->limit($limit)
			->get()
			->map(function($tag) use($mastodonMode) {
				$res = [
					'name' => $tag->name,
					'url'  => $tag->url()
				];

				if(!$mastodonMode) {
					$res['history'] = [];
					$res['count'] = HashtagService::count($tag->id);
				}

				return $res;
			});
	}

	protected function statuses()
	{
		// Removed until we provide more relevent sorting/results
		return [];
	}

	protected function statusesById()
	{
		$mastodonMode = self::$mastodonMode;
		$accountId = $this->query->input('account_id');
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		$results = Status::where('caption', 'like', $query)
			->whereProfileId($accountId)
			->limit($limit)
			->get()
			->map(function($status) use($mastodonMode) {
				return $mastodonMode ?
					StatusService::getMastodon($status->id) :
					StatusService::get($status->id);
			})
			->filter(function($status) {
				return $status && isset($status['account']);
			});
		return $results;
	}

	protected function resolveQuery()
	{
		$mastodonMode = self::$mastodonMode;
		$query = urldecode($this->query->input('q'));
		if(Helpers::validateLocalUrl($query)) {
			if(Str::contains($query, '/p/')) {
				return $this->resolveLocalStatus();
			} else {
				return $this->resolveLocalProfile();
			}
		} else {
			$default =  [
				'accounts' => [],
				'hashtags' => [],
				'statuses' => [],
			];
			if(!Helpers::validateUrl($query) && strpos($query, '@') == -1) {
				return $default;
			}

			if(Str::substrCount($query, '@') == 2) {
				try {
					$res = WebfingerService::lookup($query, $mastodonMode);
				} catch (\Exception $e) {
					return $default;
				}
				if($res && isset($res['id'])) {
					$default['accounts'][] = $res;
					return $default;
				} else {
					return $default;
				}
			}

			try {
				$res = ActivityPubFetchService::get($query);
				$banned = InstanceService::getBannedDomains();
				if($res) {
					$json = json_decode($res, true);

					if(!$json || !isset($json['@context']) || !isset($json['type']) || !in_array($json['type'], ['Note', 'Person'])) {
						return [
							'accounts' => [],
							'hashtags' => [],
							'statuses' => [],
						];
					}

					switch($json['type']) {
						case 'Note':
							$obj = Helpers::statusFetch($query);
							if(!$obj || !isset($obj['id'])) {
								return $default;
							}
							$note = $mastodonMode ?
								StatusService::getMastodon($obj['id']) :
								StatusService::get($obj['id']);
							if(!$note) {
								return $default;
							}
							$default['statuses'][] = $note;
							return $default;

                        case 'Person':
							$obj = Helpers::profileFetch($query);
							if(!$obj) {
								return $default;
							}
							if(in_array($obj['domain'], $banned)) {
								return $default;
							}
							$default['accounts'][] = $mastodonMode ?
								AccountService::getMastodon($obj['id']) :
								AccountService::get($obj['id']);
							return $default;

                        default:
							return [
								'accounts' => [],
								'hashtags' => [],
								'statuses' => [],
							];
                    }
				}
			} catch (\Exception $e) {
				return [
					'accounts' => [],
					'hashtags' => [],
					'statuses' => [],
				];
			}

			return $default;
		}
	}

	protected function resolveLocalStatus()
	{
		$query = urldecode($this->query->input('q'));
		$query = last(explode('/', $query));
		$status = StatusService::getMastodon($query);
		if(!$status) {
			return [
				'accounts' => [],
				'hashtags' => [],
				'statuses' => []
			];
		}

		$res = [
			'accounts' => [],
			'hashtags' => [],
			'statuses' => [$status]
		];

		return $res;
	}

	protected function resolveLocalProfile()
	{
		$query = urldecode($this->query->input('q'));
		$query = last(explode('/', $query));
		$profile = Profile::whereNull('status')
			->whereNull('domain')
			->whereUsername($query)
			->first();

		if(!$profile) {
			return [
				'accounts' => [],
				'hashtags' => [],
				'statuses' => []
			];
		}

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		return [
			'accounts' => $fractal->createData($resource)->toArray(),
			'hashtags' => [],
			'statuses' => []
		];
	}

}
