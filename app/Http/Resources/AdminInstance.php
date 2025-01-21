<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'domain' => $this->domain,
            'software' => $this->software,
            'unlisted' => (bool) $this->unlisted,
            'auto_cw' => (bool) $this->auto_cw,
            'banned' => (bool) $this->banned,
            'user_count' => $this->user_count,
            'status_count' => $this->status_count,
            'last_crawled_at' => $this->last_crawled_at,
            'notes' => $this->notes,
            'base_domain' => $this->base_domain,
            'ban_subdomains' => $this->ban_subdomains,
            'actors_last_synced_at' => $this->actors_last_synced_at,
            'created_at' => $this->created_at,
        ];
    }
}
