<?php

namespace App\Http\Resources;

use App\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AccountService;

/**
 * @property Profile $resource
 */
class AdminProfile extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $res = AccountService::get($this->resource->id, true);
        $res['domain'] = $this->resource->domain;
        $res['status'] = $this->resource->status;
        $res['limits'] = [
            'exist' => $this->resource->cw || $this->resource->unlisted || $this->resource->no_autolink,
            'autocw' => (bool) $this->resource->cw,
            'unlisted' => (bool) $this->resource->unlisted,
            'no_autolink' => (bool) $this->resource->no_autolink,
            'banned' => (bool) $this->resource->status == 'banned'
        ];
        return $res;
    }
}
