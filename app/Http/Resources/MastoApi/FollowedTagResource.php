<?php

namespace App\Http\Resources\MastoApi;

use App\Services\HashtagService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $hashtag_id
 */
class FollowedTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $tag = HashtagService::get($this->hashtag_id);

        if (! $tag || ! isset($tag['name'])) {
            return [];
        }

        return [
            'name' => $tag['name'],
            'url' => config('app.url').'/i/web/hashtag/'.$tag['slug'],
            'history' => [],
            'following' => true,
        ];
    }
}
