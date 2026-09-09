<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Models\Profile;
use Illuminate\Support\Facades\Cache;
use League\Fractal;

class AccountTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $local = $profile->domain == null;
        $username = $local ? $profile->username : explode('@', substr($profile->username, 1))[0];

        $res = [
            'id' => (string) $profile->id,
            'username' => $username,
            'acct' => $username,
            'display_name' => $profile->name,
            'locked' => (bool) $profile->is_private,
            'bot' => false,
            'created_at' => $profile->created_at->toJSON(),
            'note' => $profile->bio ?? '',
            'url' => $profile->url(),
            'avatar' => $profile->avatarUrl(),
            'avatar_static' => $profile->avatarUrl(),
            'header' => url('/storage/headers/missing.png'),
            'header_static' => url('/storage/headers/missing.png'),
            'followers_count' => (int) $profile->followerCount(),
            'following_count' => (int) $profile->followingCount(),
            'statuses_count' => (int) $profile->statusCount(),
            'last_status_at' => $profile->last_status_at?->toJSON(),
            'emojis' => [],
            'moved' => $this->resolveMoved($profile),
            'fields' => [],
        ];

        return $res;
    }

    protected function resolveMoved(Profile $profile): ?array
    {
        $targetId = $profile->moved_to_profile_id;

        if (! $targetId || (string) $targetId === (string) $profile->id) {
            return null;
        }

        return Cache::remember('pf:acct-trans:moved:'.$targetId, 3600, function () use ($targetId) {
            $target = Profile::find($targetId);
            if (! $target) {
                return null;
            }

            $targetLocal = $target->user_id && $target->private_key != null;

            return [
                'id' => (string) $target->id,
                'acct' => $targetLocal ? $target->username : substr($target->username, 1),
                'avatar' => $target->avatarUrl(),
            ];
        });
    }
}
