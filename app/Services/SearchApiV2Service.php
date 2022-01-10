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

	public static function query($query)
	{
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
					break;
				case 'hashtags':
					return [
						'accounts' => [],
						'hashtags' => $this->hashtags(),
						'statuses' => []
					];
					break;
				case 'statuses':
					return [
						'accounts' => [],
						'hashtags' => [],
						'statuses' => $this->statuses()
					];
					break;
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
		$user = request()->user();
		$limit = $this->query->input('limit') ?? 20;
		$offset = $this->query->input('offset') ?? 0;
		$query = '%' . $this->query->input('q') . '%';
		$results = Profile::select('profiles.*', 'followers.profile_id', 'followers.created_at')
			->whereNull('status')
			->leftJoin('followers', function($join) use($user) {
				return $join->on('profiles.id', '=', 'followers.following_id')
					->where('followers.profile_id', $user->profile_id);
			})
			->where('username', 'like', $query)
			->orderByDesc('profiles.followers_count')
			->orderByDesc('followers.created_at')
			->offset($offset)
			->limit($limit)
			->get()
			->map(function($res) {
				return AccountService::get($res['id']);
			});

		return $results;
	}

	protected function hashtags()
	{
		$limit = $this->query->input('limit') ?? 20;
		$offset = $this->query->input('offset') ?? 0;
		$query = '%' . $this->query->input('q') . '%';
		return Hashtag::whereIsBanned(false)
			->where('name', 'like', $query)
			->offset($offset)
			->limit($limit)
			->get()
			->map(function($tag) {
				return [
					'name' => $tag->name,
					'url'  => $tag->url(),
					'count' => HashtagService::count($tag->id),
					'history' => []
				];
			});
	}

	protected function statuses()
	{
		// Removed until we provide more relevent sorting/results
		return [];
	}

	protected function statusesById()
	{
		$accountId = $this->query->input('account_id');
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		$results = Status::where('caption', 'like', $query)
			->whereProfileId($accountId)
			->limit($limit)
			->get()
			->map(function($status) {
				return StatusService::get($status->id);
			});
		return $results;
	}

	protected function resolveQuery()
	{
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
					$res = WebfingerService::lookup($query);
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
							if(!$obj) {
								return $default;
							}
							$default['statuses'][] = StatusService::get($obj['id']);
							return $default;
						break;

						case 'Person':
							$obj = Helpers::profileFetch($query);
							if(!$obj) {
								return $default;
							}
							$default['accounts'][] = AccountService::get($obj['id']);
							return $default;
						break;

						default:
							return [
								'accounts' => [],
								'hashtags' => [],
								'statuses' => [],
							];
						break;
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
		$status = Status::whereNull('uri')
			->whereScope('public')
			->find($query);

		if(!$status) {
			return [
				'accounts' => [],
				'hashtags' => [],
				'statuses' => []
			];
		}

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new StatusTransformer());
		return [
			'accounts' => [],
			'hashtags' => [],
			'statuses' => $fractal->createData($resource)->toArray()
		];
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
