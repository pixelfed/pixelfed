<?php

namespace App\Http\Resources;

use App\Instance;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Instance $resource
 */
class AdminInstance extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'domain' => $this->resource->domain,
            'software' => $this->resource->software,
            'unlisted' => (bool) $this->resource->unlisted,
            'auto_cw' => (bool) $this->resource->auto_cw,
            'banned' => (bool) $this->resource->banned,
            'user_count' => $this->resource->user_count,
            'status_count' => $this->resource->status_count,
            'last_crawled_at' => $this->resource->last_crawled_at,
            'notes' => $this->resource->notes,
            'base_domain' => $this->resource->base_domain,
            'ban_subdomains' => $this->resource->ban_subdomains,
            'actors_last_synced_at' => $this->resource->actors_last_synced_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
