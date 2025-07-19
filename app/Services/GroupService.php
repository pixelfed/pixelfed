<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupInteraction;
use App\Models\GroupLimit;
use App\Models\GroupMember;
use App\Models\GroupPost;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use Cache;
use Purify;

class GroupService
{
    const CACHE_KEY = 'pf:services:groups:';

    protected static function key($name)
    {
        return self::CACHE_KEY.$name;
    }

    public static function get($id, $pid = false)
    {
        $res = Cache::remember(
            self::key($id),
            1209600,
            function () use ($id) {
                $group = (new Group)->withoutRelations()->whereNull('status')->find($id);

                if (! $group) {
                    return null;
                }

                $admin = $group->profile_id ? AccountService::get($group->profile_id) : null;

                return [
                    'id' => (string) $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'short_description' => str_limit(strip_tags($group->description), 120),
                    'category' => self::categoryById($group->category_id),
                    'local' => (bool) $group->local,
                    'url' => $group->url(),
                    'shorturl' => url('/g/'.HashidService::encode($group->id)),
                    'membership' => $group->getMembershipType(),
                    'member_count' => $group->members()->whereJoinRequest(false)->count(),
                    'verified' => false,
                    'self' => null,
                    'admin' => $admin,
                    'config' => [
                        'recommended' => (bool) $group->recommended,
                        'discoverable' => (bool) $group->discoverable,
                        'activitypub' => (bool) $group->activitypub,
                        'is_nsfw' => (bool) $group->is_nsfw,
                        'dms' => (bool) $group->dms,
                    ],
                    'metadata' => $group->metadata,
                    'created_at' => $group->created_at->toAtomString(),
                ];
            }
        );

        if ($pid) {
            $res['self'] = self::getSelf($id, $pid);
        }

        return $res;
    }

    public static function del($id)
    {
        Cache::forget('ap:groups:object:'.$id);

        return Cache::forget(self::key($id));
    }

    public static function getSelf($gid, $pid)
    {
        return Cache::remember(
            self::key('self:gid-'.$gid.':pid-'.$pid),
            3600,
            function () use ($gid, $pid) {
                $group = Group::find($gid);

                if (! $gid || ! $pid) {
                    return [
                        'is_member' => false,
                        'role' => null,
                        'is_requested' => null,
                    ];
                }

                return [
                    'is_member' => $group->isMember($pid),
                    'role' => $group->selfRole($pid),
                    'is_requested' => optional($group->members()->whereProfileId($pid)->first())->join_request ?? false,
                ];
            }
        );
    }

    public static function delSelf($gid, $pid)
    {
        Cache::forget(self::key("is_member:{$gid}:{$pid}"));

        return Cache::forget(self::key('self:gid-'.$gid.':pid-'.$pid));
    }

    public static function sidToGid($gid, $pid)
    {
        return Cache::remember(self::key('s2gid:'.$gid.':'.$pid), 3600, function () use ($gid, $pid) {
            return optional(GroupPost::whereGroupId($gid)->whereStatusId($pid)->first())->id;
        });
    }

    public static function membershipsByPid($pid)
    {
        return Cache::remember(self::key("mbpid:{$pid}"), 3600, function () use ($pid) {
            return GroupMember::whereProfileId($pid)->pluck('group_id');
        });
    }

    public static function config()
    {
        return [
            'enabled' => config('exp.gps') ?? false,
            'limits' => [
                'group' => [
                    'max' => 999,
                    'federation' => false,
                ],

                'user' => [
                    'create' => [
                        'new' => true,
                        'max' => 10,
                    ],
                    'join' => [
                        'max' => 10,
                    ],
                    'invite' => [
                        'max' => 20,
                    ],
                ],
            ],
            'guest' => [
                'public' => false,
            ],
        ];
    }

    public static function fetchRemote($url)
    {
        // todo: refactor this demo
        $res = Helpers::fetchFromUrl($url);

        if (! $res || ! isset($res['type']) || $res['type'] != 'Group') {
            return false;
        }

        $group = Group::whereRemoteUrl($url)->first();

        if ($group) {
            return $group;
        }

        $group = new Group;
        $group->remote_url = $res['url'];
        $group->name = $res['name'];
        $group->inbox_url = $res['inbox'];
        $group->metadata = [
            'header' => [
                'url' => $res['icon']['image']['url'],
            ],
        ];
        $group->description = Purify::clean($res['summary']);
        $group->local = false;
        $group->save();

        return $group->url();
    }

    public static function log(
        string $groupId,
        string $profileId,
        ?string $type = null,
        ?array $meta = null,
        ?string $itemType = null,
        ?string $itemId = null
    ) {
        // todo: truncate (some) metadata after XX days in cron/queue
        $log = new GroupInteraction;
        $log->group_id = $groupId;
        $log->profile_id = $profileId;
        $log->type = $type;
        $log->item_type = $itemType;
        $log->item_id = $itemId;
        $log->metadata = $meta;
        $log->save();
    }

    public static function getRejoinTimeout($gid, $pid)
    {
        $key = self::key('rejoin-timeout:gid-'.$gid.':pid-'.$pid);

        return Cache::has($key);
    }

    public static function setRejoinTimeout($gid, $pid)
    {
        // todo: allow group admins to manually remove timeout
        $key = self::key('rejoin-timeout:gid-'.$gid.':pid-'.$pid);

        return Cache::put($key, 1, 86400);
    }

    public static function getMemberInboxes($id)
    {
        // todo: cache this, maybe add join/leave methods to this service to handle cache invalidation
        $group = (new Group)->withoutRelations()->findOrFail($id);
        if (! $group->local) {
            return [];
        }
        $members = GroupMember::whereGroupId($id)->whereLocalProfile(false)->pluck('profile_id');

        return Profile::find($members)->map(function ($u) {
            return $u->sharedInbox ?? $u->inbox_url;
        })->toArray();
    }

    public static function getInteractionLimits($gid, $pid)
    {
        return Cache::remember(self::key(":il:{$gid}:{$pid}"), 3600, function () use ($gid, $pid) {
            $limit = GroupLimit::whereGroupId($gid)->whereProfileId($pid)->first();
            if (! $limit) {
                return [
                    'limits' => [
                        'can_post' => true,
                        'can_comment' => true,
                        'can_like' => true,
                    ],
                    'updated_at' => null,
                ];
            }

            return [
                'limits' => $limit->limits,
                'updated_at' => $limit->updated_at->format('c'),
            ];
        });
    }

    public static function clearInteractionLimits($gid, $pid)
    {
        return Cache::forget(self::key(":il:{$gid}:{$pid}"));
    }

    public static function canPost($gid, $pid)
    {
        $limits = self::getInteractionLimits($gid, $pid);
        if ($limits) {
            return (bool) $limits['limits']['can_post'];
        } else {
            return true;
        }
    }

    public static function canComment($gid, $pid)
    {
        $limits = self::getInteractionLimits($gid, $pid);
        if ($limits) {
            return (bool) $limits['limits']['can_comment'];
        } else {
            return true;
        }
    }

    public static function canLike($gid, $pid)
    {
        $limits = self::getInteractionLimits($gid, $pid);
        if ($limits) {
            return (bool) $limits['limits']['can_like'];
        } else {
            return true;
        }
    }

    public static function categories($onlyActive = true)
    {
        return Cache::remember(self::key(':categories'), 2678400, function () use ($onlyActive) {
            return GroupCategory::when($onlyActive, function ($q, $onlyActive) {
                return $q->whereActive(true);
            })
                ->orderBy('order')
                ->pluck('name')
                ->toArray();
        });
    }

    public static function categoryById($id)
    {
        return Cache::remember(self::key(':categorybyid:'.$id), 2678400, function () use ($id) {
            $category = GroupCategory::find($id);
            if ($category) {
                return [
                    'name' => $category->name,
                    'url' => url("/groups/explore/category/{$category->slug}"),
                ];
            }

            return false;
        });
    }

    public static function isMember($gid = false, $pid = false)
    {
        if (! $gid || ! $pid) {
            return false;
        }

        $key = self::key("is_member:{$gid}:{$pid}");

        return Cache::remember($key, 3600, function () use ($gid, $pid) {
            return GroupMember::whereGroupId($gid)
                ->whereProfileId($pid)
                ->whereJoinRequest(false)
                ->exists();
        });
    }

    public static function mutualGroups($cid = false, $pid = false, $exclude = [])
    {
        if (! $cid || ! $pid) {
            return [
                'count' => 0,
                'groups' => [],
            ];
        }

        $self = self::membershipsByPid($cid);
        $user = self::membershipsByPid($pid);

        if (! $self->count() || ! $user->count()) {
            return [
                'count' => 0,
                'groups' => [],
            ];
        }

        $intersect = $self->intersect($user);
        $count = $intersect->count();
        $groups = $intersect
            ->values()
            ->filter(function ($id) use ($exclude) {
                return ! in_array($id, $exclude);
            })
            ->shuffle()
            ->take(1)
            ->map(function ($id) {
                return self::get($id);
            });

        return [
            'count' => $count,
            'groups' => $groups,
        ];
    }
}
