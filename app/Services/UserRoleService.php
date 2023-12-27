<?php

namespace App\Services;

use App\Models\UserRoles;

class UserRoleService
{
    public static function can($action, $id)
    {
        $roles = self::get($id);

        return in_array($action, $roles) ? $roles[$action] : null;
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

            'can-post' => true,
            'can-comment' => true,
            'can-like' => true,
            'can-share' => true,

            'can-follow' => false,
            'can-make-public' => false,
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
        ];
    }
}
