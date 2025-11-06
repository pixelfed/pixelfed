<?php

namespace App\Http\Resources;

use App\AccountInterstitial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AccountService;
use App\Services\StatusService;

/**
 * @property AccountInterstitial $resource
 */
class AdminSpamReport extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		$res = [
			'id' => $this->resource->id,
			'type' => $this->resource->type,
			'status' => null,
			'read_at' => $this->resource->read_at,
			'created_at' => $this->resource->created_at,
		];

		if($this->resource->item_id && $this->resource->item_type === 'App\Status') {
			$res['status'] = StatusService::get($this->resource->item_id, false);
		}

		return $res;
	}
}
