<?php

namespace App\Transformer\Api;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\PronounService;
use Illuminate\Support\Facades\Cache;
use League\Fractal;

class AccountTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        // 'relationship',
    ];

    public function transform(Profile $profile)
    {
        if (! $profile) {
            return [];
        }

        $adminIds = Cache::remember('pf:admin-ids', 604800, function () {
            return User::whereIsAdmin(true)->pluck('profile_id')->toArray();
        });

        $local = $profile->private_key != null;
        $local = $profile->user_id && $profile->private_key != null;
        $hideFollowing = false;
        $hideFollowers = false;
        if ($local) {
            $hideFollowing = Cache::remember('pf:acct-trans:hideFollowing:'.$profile->id, 2592000, function () use ($profile) {
                $settings = UserSetting::whereUserId($profile->user_id)->first();
                if (! $settings) {
                    return false;
                }

                return $settings->show_profile_following_count == false;
            });
            $hideFollowers = Cache::remember('pf:acct-trans:hideFollowers:'.$profile->id, 2592000, function () use ($profile) {
                $settings = UserSetting::whereUserId($profile->user_id)->first();
                if (! $settings) {
                    return false;
                }

                return $settings->show_profile_follower_count == false;
            });
        }
        $is_admin = ! $local ? false : in_array($profile->id, $adminIds);
        $acct = $local ? $profile->username : substr($profile->username, 1);
        $username = $local ? $profile->username : explode('@', $acct)[0];
        $res = [
            'id' => (string) $profile->id,
            'username' => $username,
            'acct' => $acct,
            'display_name' => $profile->name,
            'discoverable' => true,
            'locked' => (bool) $profile->is_private,
            'followers_count' => $hideFollowers ? 0 : (int) $profile->followers_count,
            'following_count' => $hideFollowing ? 0 : (int) $profile->following_count,
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
            'last_fetched_at' => $profile->last_fetched_at?->toJSON(),
            'pronouns' => PronounService::get($profile->id),
            'location' => $profile->location,
        ];

        if ($profile->moved_to_profile_id) {
            $newProfile = AccountService::get($profile->moved_to_profile_id);
            if ($newProfile && isset($newProfile['id'], $newProfile['acct'])) {
                $res['moved'] = [
                    'id' => $newProfile['id'],
                    'acct' => $newProfile['acct'],
                    'avatar' => $newProfile['avatar'],
                ];
            }
        }

        return $res;
    }

    protected function includeRelationship(Profile $profile)
    {
        return $this->item($profile, new RelationshipTransformer);
    }
}
