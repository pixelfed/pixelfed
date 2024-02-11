<?php

namespace App\Http\Resources\MastoApi;

use App\Services\HashtagService;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowedTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
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
