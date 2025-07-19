<?php

namespace App\Services\Groups;

use App\Models\GroupMember;
use App\Services\AccountService;
use App\Services\GroupService;
use Cache;

class GroupAccountService
{
    const CACHE_KEY = 'pf:services:groups:accounts-v0:';

    public static function get($gid, $pid)
    {
        $group = GroupService::get($gid);
        if (! $group) {
            return;
        }

        $account = AccountService::get($pid, true);
        if (! $account) {
            return;
        }

        $key = self::CACHE_KEY.$gid.':'.$pid;
        $account['group'] = Cache::remember($key, 3600, function () use ($gid, $pid) {
            $membership = GroupMember::whereGroupId($gid)->whereProfileId($pid)->first();
            if (! $membership) {
                return [];
            }

            return [
                'joined' => $membership->created_at->format('c'),
                'role' => $membership->role,
                'local_group' => (bool) $membership->local_group,
                'local_profile' => (bool) $membership->local_profile,
            ];
        });

        return $account;
    }

    public static function del($gid, $pid)
    {
        $key = self::CACHE_KEY.$gid.':'.$pid;

        return Cache::forget($key);
    }
}
