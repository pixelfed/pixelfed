<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Profile;
use League\Fractal;
use Illuminate\Support\Str;

class AccountTransformer extends Fractal\TransformerAbstract
{
	public function transform(Profile $profile): array
	{
		$local = $profile->domain == null;
		$is_admin = !$local ? false : $profile->user->is_admin;
		$username = $local ? $profile->username : explode('@', substr($profile->username, 1))[0];
		return [
			'id' => (string) $profile->id,
			'username' => $username,
			'acct' => $username,
			'display_name' => $profile->name,
			'locked' => (bool) $profile->is_private,
			'created_at' => $profile->created_at->toJSON(),
			'followers_count' => $profile->followerCount(),
			'following_count' => $profile->followingCount(),
			'statuses_count' => (int) $profile->statusCount(),
			'note' => $profile->bio ?? '',
			'url' => $profile->url(),
			'avatar' => $profile->avatarUrl(),
			'avatar_static' => $profile->avatarUrl(),
			'header' => '',
			'header_static' => '',
			'emojis' => [],
			'moved' => null,
			'fields' => null,
			'bot' => false,
			'software' => 'pixelfed',
			'is_admin' => (bool) $is_admin,
		];
	}
}
