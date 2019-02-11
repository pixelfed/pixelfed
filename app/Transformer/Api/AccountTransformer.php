<?php

namespace App\Transformer\Api;

use App\Profile;
use League\Fractal;

class AccountTransformer extends Fractal\TransformerAbstract
{
	public function transform(Profile $profile)
	{
		$is_admin = $profile->domain ? false : $profile->user->is_admin;
		return [
			'id' => (string) $profile->id,
			'username' => $profile->username,
			'acct' => $profile->username,
			'display_name' => $profile->name,
			'locked' => (bool) $profile->is_private,
			'created_at' => $profile->created_at->format('c'),
			'followers_count' => $profile->followerCount(),
			'following_count' => $profile->followingCount(),
			'statuses_count' => $profile->statusCount(),
			'note' => $profile->bio,
			'url' => $profile->url(),
			'avatar' => $profile->avatarUrl(),
			'avatar_static' => $profile->avatarUrl(),
			'header' => null,
			'header_static' => null,
			'moved' => null,
			'fields' => null,
			'bot' => null,
			'website' => $profile->website,
			'software' => 'pixelfed',
			'is_admin' => (bool) $is_admin
		];
	}
}
