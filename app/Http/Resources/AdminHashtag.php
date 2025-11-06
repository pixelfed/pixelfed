<?php

namespace App\Http\Resources;

use App\Hashtag;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Hashtag $resource
 */
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
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'can_trend' => $this->resource->can_trend === null ? true : (bool) $this->resource->can_trend,
            'can_search' => $this->resource->can_search === null ? true : (bool) $this->resource->can_search,
            'is_nsfw' => (bool) $this->resource->is_nsfw,
            'is_banned' => (bool) $this->resource->is_banned,
            'cached_count' => $this->resource->cached_count ?? 0,
            'created_at' => $this->resource->created_at
        ];
    }
}
