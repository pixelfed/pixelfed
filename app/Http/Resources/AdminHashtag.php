<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool|null $can_trend
 * @property bool|null $can_search
 * @property bool $is_nsfw
 * @property bool $is_banned
 * @property int|null $cached_count
 * @property Carbon $created_at
 */
class AdminHashtag extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
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
            'created_at' => $this->created_at,
        ];
    }
}
