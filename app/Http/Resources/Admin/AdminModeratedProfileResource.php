<?php

namespace App\Http\Resources\Admin;

use App\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $domain
 * @property int $profile_id
 * @property string|null $profile_url
 * @property string|null $note
 * @property bool $is_banned
 * @property bool $is_nsfw
 * @property bool $is_unlisted
 * @property bool $is_noautolink
 * @property bool $is_nodms
 * @property bool $is_notrending
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminModeratedProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profileObj = [];
        $profile = Profile::withTrashed()->find($this->profile_id);
        if ($profile) {
            $profileObj = [
                'name' => $profile->name,
                'username' => $profile->username,
                'username_str' => explode('@', $profile->username)[1],
                'remote_url' => $profile->remote_url,
            ];
        }

        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'profile' => $profileObj,
            'profile_id' => $this->profile_id,
            'profile_url' => $this->profile_url,
            'note' => $this->note,
            'is_banned' => (bool) $this->is_banned,
            'is_nsfw' => (bool) $this->is_nsfw,
            'is_unlisted' => (bool) $this->is_unlisted,
            'is_noautolink' => (bool) $this->is_noautolink,
            'is_nodms' => (bool) $this->is_nodms,
            'is_notrending' => (bool) $this->is_notrending,
            'created_at' => now()->parse($this->created_at)->format('c'),
        ];
    }
}
