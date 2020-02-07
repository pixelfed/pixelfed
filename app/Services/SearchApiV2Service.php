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

		if($query->has('resolve') && $query->resolve == true && Helpers::validateUrl($query)) {
			return [
				'accounts' => [
					$this->resolve()
				],
				'hashtags' => [],
				'statuses' => []
			];
		}

		if($query->has('type')) {
			switch ($query->input('type')) {
				case 'accounts':
					return [
						'accounts' => [
							$this->accounts()
						],
						'hashtags' => [],
						'statuses' => []
					];
					break;
				case 'hashtags':
					return [
						'accounts' => [],
						'hashtags' => [
							$this->hashtags()
						],
						'statuses' => []
					];
					break;
				case 'statuses':
					return [
						'accounts' => [],
						'hashtags' => [],
						'statuses' => [
							$this->statuses()
						]
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

	protected function resolve()
	{
		return WebfingerService::lookup($this->query->input('q'));
	}

	protected function accounts()
	{
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		$results = Profile::whereNull('status')
			->where('username', 'like', $query)
			->when($this->query->input('offset') != null, function($q, $offset) {
				return $q->offset($offset);
			})
			->limit($limit)
			->get();

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Collection($results, new AccountTransformer());
		return $fractal->createData($resource)->toArray();
	}

	protected function hashtags()
	{
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		return Hashtag::whereIsBanned(false)
			->where('name', 'like', $query)
			->when($this->query->input('offset') != null, function($q, $offset) {
				return $q->offset($offset);
			})
			->limit($limit)
			->get()
			->map(function($tag) {
				return [
					'name' => $tag->name,
					'url'  => $tag->url(),
					'history' => []
				];
			});
	}

	protected function statuses()
	{
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		$results = Status::where('caption', 'like', $query)
			->when($this->query->input('offset') != null, function($q, $offset) {
				return $q->offset($offset);
			})
			->limit($limit)
			->get();

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Collection($results, new StatusTransformer());
		return $fractal->createData($resource)->toArray();
	}

	protected function statusesById()
	{
		$accountId = $this->query->input('account_id');
		$limit = $this->query->input('limit', 20);
		$query = '%' . $this->query->input('q') . '%';
		$results = Status::where('caption', 'like', $query)
			->whereProfileId($accountId)
			->when($this->query->input('offset') != null, function($q, $offset) {
				return $q->offset($offset);
			})
			->limit($limit)
			->get();

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Collection($results, new StatusTransformer());
		return $fractal->createData($resource)->toArray();
	}

}