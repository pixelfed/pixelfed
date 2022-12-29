<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminHashtag extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'can_trend' => $this->can_trend === null ? true : (bool) $this->can_trend,
            'can_search' => $this->can_search === null ? true : (bool) $this->can_search,
            'is_nsfw' => (bool) $this->is_nsfw,
            'is_banned' => (bool) $this->is_banned,
            'cached_count' => $this->cached_count ?? 0,
            'created_at' => $this->created_at
        ];
    }
}
