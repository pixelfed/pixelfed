<?php

namespace App\Http\Resources;

use App\Services\AccountService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string|null $name
 * @property string $username
 */
class DirectoryProfile extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $account = AccountService::get($this->id, true);
        if (! $account) {
            return [];
        }

        $url = url($this->username);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'url' => $url,
            'avatar' => $account['avatar'],
            'following_count' => $account['following_count'],
            'followers_count' => $account['followers_count'],
            'statuses_count' => $account['statuses_count'],
            'bio' => $account['note_text'],
        ];
    }
}
