<?php

namespace App\Services;

use App\Models\UserRoles;

class UserRoleService
{
    public static function can($action, $id, $useDefaultFallback = true)
    {
        $default = self::defaultRoles();
        $roles = self::get($id);
        return
            in_array($action, array_keys($roles)) ?
                $roles[$action] :
                (
                    $useDefaultFallback ?
                        $default[$action] :
                        false
                );
        }

    public static function get($id)
    {
        if($roles = UserRoles::whereUserId($id)->first()) {
            return $roles->roles;
        }

        return self::defaultRoles();
    }

    public static function roleKeys()
    {
        return array_keys(self::defaultRoles());
    }

    public static function defaultRoles()
    {
        return [
            'account-force-private' => true,
            'account-ignore-follow-requests' => true,

            'can-view-public-feed' => true,
            'can-view-network-feed' => true,
            'can-view-discover' => true,
            'can-view-hashtag-feed' => false,

            'can-post' => true,
            'can-comment' => true,
            'can-like' => true,
            'can-share' => true,

            'can-follow' => false,
            'can-make-public' => false,

            'can-direct-message' => false,
            'can-use-stories' => false,
            'can-view-sensitive' => false,
            'can-bookmark' => false,
            'can-collections' => false,
            'can-federation' => false,
        ];
    }

    public static function getRoles($id)
    {
        $myRoles = self::get($id);
        $roleData = collect(self::roleData())
            ->map(function($role, $k) use($myRoles) {
                $role['value'] = $myRoles[$k];
                return $role;
            })
            ->toArray();
        return $roleData;
    }

    public static function roleData()
    {
        return [
            'account-force-private' => [
                'title' => 'Force Private Account',
                'action' => 'Prevent changing account from private'
            ],
            'account-ignore-follow-requests' => [
                'title' => 'Ignore Follow Requests',
                'action' => 'Hide follow requests and associated notifications'
            ],
            'can-view-public-feed' => [
                'title' => 'Hide Public Feed',
                'action' => 'Hide the public feed timeline'
            ],
            'can-view-network-feed' => [
                'title' => 'Hide Network Feed',
                'action' => 'Hide the network feed timeline'
            ],
            'can-view-discover' => [
                'title' => 'Hide Discover',
                'action' => 'Hide the discover feature'
            ],
            'can-post' => [
                'title' => 'Can post',
                'action' => 'Allows new posts to be shared'
            ],
            'can-comment' => [
                'title' => 'Can comment',
                'action' => 'Allows new comments to be posted'
            ],
            'can-like' => [
                'title' => 'Can Like',
                'action' => 'Allows the ability to like posts and comments'
            ],
            'can-share' => [
                'title' => 'Can Share',
                'action' => 'Allows the ability to share posts and comments'
            ],
            'can-follow' => [
                'title' => 'Can Follow',
                'action' => 'Allows the ability to follow accounts'
            ],
            'can-make-public' => [
                'title' => 'Can make account public',
                'action' => 'Allows the ability to make account public'
            ],

            'can-direct-message' => [
                'title' => '',
                'action' => ''
            ],
            'can-use-stories' => [
                'title' => '',
                'action' => ''
            ],
            'can-view-sensitive' => [
                'title' => '',
                'action' => ''
            ],
            'can-bookmark' => [
                'title' => '',
                'action' => ''
            ],
            'can-collections' => [
                'title' => '',
                'action' => ''
            ],
            'can-federation' => [
                'title' => '',
                'action' => ''
            ],
        ];
    }

    public static function mapInvite($id, $data = [])
    {
        $roles = self::get($id);

        $map = [
            'account-force-private' => 'private',
            'account-ignore-follow-requests' => 'private',

            'can-view-public-feed' => 'discovery_feeds',
            'can-view-network-feed' => 'discovery_feeds',
            'can-view-discover' => 'discovery_feeds',
            'can-view-hashtag-feed' => 'discovery_feeds',

            'can-post' => 'post',
            'can-comment' => 'comment',
            'can-like' => 'like',
            'can-share' => 'share',

            'can-follow' => 'follow',
            'can-make-public' => '!private',

            'can-direct-message' => 'dms',
            'can-use-stories' => 'story',
            'can-view-sensitive' => '!hide_cw',
            'can-bookmark' => 'bookmark',
            'can-collections' => 'collection',
            'can-federation' => 'federation',
        ];

        foreach ($map as $key => $value) {
            if(!isset($data[$value]) && !isset($data[substr($value, 1)])) {
                $map[$key] = false;
                continue;
            }
            $map[$key] = str_starts_with($value, '!') ? !$data[substr($value, 1)] : $data[$value];
        }

        return $map;
    }

    public static function mapActions($id, $data = [])
    {
        $res = [];
        $map = [
            'account-force-private' => 'private',
            'account-ignore-follow-requests' => 'private',

            'can-view-public-feed' => 'discovery_feeds',
            'can-view-network-feed' => 'discovery_feeds',
            'can-view-discover' => 'discovery_feeds',
            'can-view-hashtag-feed' => 'discovery_feeds',

            'can-post' => 'post',
            'can-comment' => 'comment',
            'can-like' => 'like',
            'can-share' => 'share',

            'can-follow' => 'follow',
            'can-make-public' => '!private',

            'can-direct-message' => 'dms',
            'can-use-stories' => 'story',
            'can-view-sensitive' => '!hide_cw',
            'can-bookmark' => 'bookmark',
            'can-collections' => 'collection',
            'can-federation' => 'federation',
        ];

        foreach ($map as $key => $value) {
            if(!isset($data[$value]) && !isset($data[substr($value, 1)])) {
                $res[$key] = false;
                continue;
            }
            $res[$key] = str_starts_with($value, '!') ? !$data[substr($value, 1)] : $data[$value];
        }

        return $res;
    }
}
