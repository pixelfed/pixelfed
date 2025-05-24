<?php

namespace App\Services;

use App\Status;
use App\Transformer\Api\StatusStatelessTransformer;
use Illuminate\Support\Facades\Cache;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusService
{
    const CACHE_KEY = 'pf:services:status:v1.1:';

    const MAX_PINNED = 3;

    public static function key($id, $publicOnly = true)
    {
        $p = $publicOnly ? 'pub:' : 'all:';

        return self::CACHE_KEY.$p.$id;
    }

    public static function get($id, $publicOnly = true, $mastodonMode = false)
    {
        $res = Cache::remember(self::key($id, $publicOnly), 21600, function () use ($id, $publicOnly) {
            if ($publicOnly) {
                $status = Status::whereScope('public')->find($id);
            } else {
                $status = Status::whereIn('scope', ['public', 'private', 'unlisted', 'group'])->find($id);
            }
            if (! $status) {
                return null;
            }
            $fractal = new Fractal\Manager;
            $fractal->setSerializer(new ArraySerializer);
            $resource = new Fractal\Resource\Item($status, new StatusStatelessTransformer);
            $res = $fractal->createData($resource)->toArray();
            $res['_pid'] = isset($res['account']) && isset($res['account']['id']) ? $res['account']['id'] : null;
            if (isset($res['_pid'])) {
                unset($res['account']);
            }

            return $res;
        });
        if ($res && isset($res['_pid'])) {
            $res['account'] = $mastodonMode === true ? AccountService::getMastodon($res['_pid'], true) : AccountService::get($res['_pid'], true);
            unset($res['_pid']);
        }

        return $res;
    }

    public static function getMastodon($id, $publicOnly = true)
    {
        $status = self::get($id, $publicOnly, true);
        if (! $status) {
            return null;
        }

        if (! isset($status['account'])) {
            return null;
        }

        $status['replies_count'] = $status['reply_count'];

        if (config('exp.emc') == false) {
            return $status;
        }

        unset(
            $status['_v'],
            $status['comments_disabled'],
            $status['content_text'],
            $status['gid'],
            $status['label'],
            $status['liked_by'],
            $status['local'],
            $status['parent'],
            $status['pf_type'],
            $status['place'],
            $status['replies'],
            $status['reply_count'],
            $status['shortcode'],
            $status['taggedPeople'],
            $status['thread'],
            $status['account']['header_bg'],
            $status['account']['is_admin'],
            $status['account']['last_fetched_at'],
            $status['account']['local'],
            $status['account']['location'],
            $status['account']['note_text'],
            $status['account']['pronouns'],
            $status['account']['website'],
            $status['media_attachments'],
        );
        $status['account']['avatar_static'] = $status['account']['avatar'];
        $status['account']['bot'] = false;
        $status['account']['emojis'] = [];
        $status['account']['fields'] = [];
        $status['account']['header'] = url('/storage/headers/missing.png');
        $status['account']['header_static'] = url('/storage/headers/missing.png');
        $status['account']['last_status_at'] = null;

        $status['media_attachments'] = array_values(MediaService::getMastodon($status['id']));
        $status['muted'] = false;
        $status['reblogged'] = false;

        return $status;
    }

    public static function getState($id, $pid)
    {
        $status = self::get($id, false);

        if (! $status || ! $pid) {
            return [
                'liked' => false,
                'shared' => false,
                'bookmarked' => false,
            ];
        }

        return [
            'liked' => LikeService::liked($pid, $id),
            'shared' => self::isShared($id, $pid),
            'bookmarked' => self::isBookmarked($id, $pid),
        ];
    }

    public static function getFull($id, $pid, $publicOnly = true)
    {
        $res = self::get($id, $publicOnly);
        if (! $res || ! isset($res['account']) || ! isset($res['account']['id'])) {
            return $res;
        }
        $res['relationship'] = RelationshipService::get($pid, $res['account']['id']);

        return $res;
    }

    public static function getDirectMessage($id)
    {
        $status = Status::whereScope('direct')->find($id);

        if (! $status) {
            return null;
        }

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, new StatusStatelessTransformer);

        return $fractal->createData($resource)->toArray();
    }

    public static function del($id, $purge = false)
    {
        if ($purge) {
            $status = self::get($id);
            if ($status && isset($status['account']) && isset($status['account']['id'])) {
                Cache::forget('profile:embed:'.$status['account']['id']);
            }
            Cache::forget('status:transformer:media:attachments:'.$id);
            MediaService::del($id);
            Cache::forget('pf:services:sh:id:'.$id);
            PublicTimelineService::rem($id);
            NetworkTimelineService::rem($id);
        }

        Cache::forget(self::key($id, false));

        return Cache::forget(self::key($id));
    }

    public static function refresh($id)
    {
        Cache::forget(self::key($id, false));
        Cache::forget(self::key($id, true));
        self::get($id, false);
        self::get($id, true);
    }

    public static function isShared($id, $pid = null)
    {
        return $pid ?
            ReblogService::get($pid, $id) :
            false;
    }

    public static function isBookmarked($id, $pid = null)
    {
        return $pid ?
            BookmarkService::get($pid, $id) :
            false;
    }

    public static function totalLocalStatuses()
    {
        return InstanceService::totalLocalStatuses();
    }

    public static function isPinned($id)
    {
        return Status::whereId($id)->whereNotNull('pinned_order')->exists();
    }

    public static function totalPins($pid)
    {
        return Status::whereProfileId($pid)->whereNotNull('pinned_order')->count();
    }

    public static function markPin($id)
    {
        $status = Status::find($id);

        if (! $status) {
            return [
                'success' => false,
                'error' => 'Record not found',
            ];
        }

        if ($status->scope != 'public') {
            return [
                'success' => false,
                'error' => 'Validation failed: you can only pin public posts',
            ];
        }

        if (self::isPinned($id)) {
            return [
                'success' => false,
                'error' => 'This post is already pinned',
            ];
        }

        $totalPins = self::totalPins($status->profile_id);

        if ($totalPins >= self::MAX_PINNED) {
            return [
                'success' => false,
                'error' => 'Validation failed: You have already pinned the max number of posts',
            ];
        }

        $status->pinned_order = $totalPins + 1;
        $status->save();

        self::refresh($id);

        return [
            'success' => true,
            'error' => null,
        ];
    }

    public static function unmarkPin($id)
    {
        $status = Status::find($id);

        if (! $status || is_null($status->pinned_order)) {
            return false;
        }

        $removedOrder = $status->pinned_order;
        $profileId = $status->profile_id;

        $status->pinned_order = null;
        $status->save();

        Status::where('profile_id', $profileId)
            ->whereNotNull('pinned_order')
            ->where('pinned_order', '>', $removedOrder)
            ->orderBy('pinned_order', 'asc')
            ->chunk(10, function ($statuses) {
                foreach ($statuses as $s) {
                    $s->pinned_order = $s->pinned_order - 1;
                    $s->save();
                }
            });

        self::refresh($id);

        return true;
    }
}
