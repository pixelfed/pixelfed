<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Profile;
use League\Fractal;
use Illuminate\Support\Str;

class AccountTransformer extends Fractal\TransformerAbstract
{
	public function transform(Profile $profile)
	{
		$local = $profile->domain == null;
		$username = $local ? $profile->username : explode('@', substr($profile->username, 1))[0];
		return [
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
			'header' =>  url('/storage/headers/missing.png'),
			'header_static' =>  url('/storage/headers/missing.png'),
			'followers_count' => (int) $profile->followerCount(),
			'following_count' => (int) $profile->followingCount(),
			'statuses_count' => (int) $profile->statusCount(),
			'last_status_at' => optional($profile->last_status_at)->toJSON(),
			'emojis' => [],
			'moved' => null,
			'fields' => [],
		];
	}
}
