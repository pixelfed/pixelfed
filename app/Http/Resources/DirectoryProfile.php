<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Cache;
use App\Services\AccountService;

class DirectoryProfile extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
	 */
	public function toArray($request)
	{
		$account = AccountService::get($this->resource->id, true);
		if(!$account) {
			return [];
		}

		$url = url($this->resource->username);
		return [
			'id' => $this->resource->id,
			'name' => $this->resource->name,
			'username' => $this->resource->username,
			'url' => $url,
			'avatar' => $account['avatar'],
			'following_count' => $account['following_count'],
			'followers_count' => $account['followers_count'],
			'statuses_count' => $account['statuses_count'],
			'bio' => $account['note_text']
		];
	}
}
