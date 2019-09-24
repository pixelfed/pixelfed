<?php

namespace App\Transformer\Api;

use Auth;
use App\Profile;
use League\Fractal;

class AccountTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        'relationship',
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
			'created_at' => $profile->created_at->format('c'),
			'followers_count' => $profile->followerCount(),
			'following_count' => $profile->followingCount(),
			'statuses_count' => (int) $profile->statusCount(),
			'note' => $profile->bio ?? '',
			'url' => $profile->url(),
			'avatar' => $profile->avatarUrl(),
			'avatar_static' => $profile->avatarUrl(),
			'header' => '',
			'header_static' => '',
			'header_bg' => $profile->header_bg,
			'emojis' => [],
			'moved' => null,
			'fields' => [],
			'bot' => false,
			'website' => $profile->website,
			'software' => 'pixelfed',
			'is_admin' => (bool) $is_admin,
		];
	}

	protected function includeRelationship(Profile $profile)
	{
		return $this->item($profile, new RelationshipTransformer());
	}
}
