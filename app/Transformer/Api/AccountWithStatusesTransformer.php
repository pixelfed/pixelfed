<?php

namespace App\Transformer\Api;

use Auth;
use App\Profile;
use League\Fractal;

class AccountWithStatusesTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        // 'relationship',
        'posts',
    ];

    public function transform(Profile $profile)
    {
        $local = $profile->domain == null;
        $is_admin = !$local ? false : $profile->user->is_admin;
        $acct = $local ? $profile->username : substr($profile->username, 1);
        $username = $local ? $profile->username : explode('@', $acct)[0];
        return [
            'id' => (string) $profile->id,
            'username' => $username,
            'acct' => $acct,
            'display_name' => $profile->name,
            'locked' => (bool) $profile->is_private,
            'followers_count' => $profile->followerCount(),
            'following_count' => $profile->followingCount(),
            'statuses_count' => (int) $profile->statusCount(),
            'note' => $profile->bio ?? '',
            'url' => $profile->url(),
            'avatar' => $profile->avatarUrl(),
            'website' => $profile->website,
            'local' => (bool) $local,
            'is_admin' => (bool) $is_admin,
            'created_at' => $profile->created_at->timestamp
        ];
    }

    protected function includePosts(Profile $profile)
    {
        $posts = $profile
                ->statuses()
                ->whereIsNsfw(false)
                ->whereType('photo')
                ->whereScope('public')
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->latest()
                ->take(5)
                ->get();

        return $this->collection($posts, new StatusStatelessTransformer());
    }
}
