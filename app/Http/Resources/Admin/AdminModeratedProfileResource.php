<?php

namespace App\Http\Resources\Admin;

use App\Profile;
use App\Models\ModeratedProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ModeratedProfile $resource
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
        $profile = Profile::withTrashed()->find($this->resource->profile_id);
        if ($profile) {
            $profileObj = [
                'name' => $profile->name,
                'username' => $profile->username,
                'username_str' => explode('@', $profile->username)[1],
                'remote_url' => $profile->remote_url,
            ];
        }

        return [
            'id' => $this->resource->id,
            'domain' => $this->resource->domain,
            'profile' => $profileObj,
            'profile_id' => $this->resource->profile_id,
            'profile_url' => $this->resource->profile_url,
            'note' => $this->resource->note,
            'is_banned' => (bool) $this->resource->is_banned,
            'is_nsfw' => (bool) $this->resource->is_nsfw,
            'is_unlisted' => (bool) $this->resource->is_unlisted,
            'is_noautolink' => (bool) $this->resource->is_noautolink,
            'is_nodms' => (bool) $this->resource->is_nodms,
            'is_notrending' => (bool) $this->resource->is_notrending,
            'created_at' => now()->parse($this->resource->created_at)->format('c'),
        ];
    }
}
