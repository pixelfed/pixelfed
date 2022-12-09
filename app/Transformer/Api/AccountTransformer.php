<?php

namespace App\Transformer\Api;

use Auth;
use Cache;
use App\Profile;
use App\User;
use League\Fractal;
use App\Services\PronounService;

class AccountTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        // 'relationship',
    ];

	public function transform(Profile $profile)
	{
		if(!$profile) {
			return [];
		}

		$adminIds = Cache::remember('pf:admin-ids', 604800, function() {
			return User::whereIsAdmin(true)->pluck('profile_id')->toArray();
		});

		$local = $profile->private_key != null;
		$is_admin = !$local ? false : in_array($profile->id, $adminIds);
		$acct = $local ? $profile->username : substr($profile->username, 1);
		$username = $local ? $profile->username : explode('@', $acct)[0];
		return [
			'id' => (string) $profile->id,
			'username' => $username,
			'acct' => $acct,
			'display_name' => $profile->name,
			'discoverable' => true,
			'locked' => (bool) $profile->is_private,
			'followers_count' => (int) $profile->followers_count,
			'following_count' => (int) $profile->following_count,
			'statuses_count' => (int) $profile->status_count,
			'note' => $profile->bio ?? '',
			'note_text' => $profile->bio ? strip_tags($profile->bio) : null,
			'url' => $profile->url(),
			'avatar' => $profile->avatarUrl(),
			'website' => $profile->website,
			'local' => (bool) $local,
			'is_admin' => (bool) $is_admin,
			'created_at' => $profile->created_at->toJSON(),
			'header_bg' => $profile->header_bg,
			'last_fetched_at' => optional($profile->last_fetched_at)->toJSON(),
			'pronouns' => PronounService::get($profile->id),
			'location' => $profile->location
		];
	}

	protected function includeRelationship(Profile $profile)
	{
		return $this->item($profile, new RelationshipTransformer());
	}
}
