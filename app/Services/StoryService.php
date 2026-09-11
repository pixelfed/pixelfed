<?php

namespace App\Services;

use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class StoryService
{
    const STORY_KEY = 'pf:services:stories:v1:';

    public static function get($id)
    {
        $account = AccountService::get($id);
        if (! $account) {
            return false;
        }

        $res = [
            'profile' => [
                'id' => (string) $account['id'],
                'avatar' => $account['avatar'],
                'username' => $account['username'],
                'url' => $account['url'],
            ],
        ];

        $res['stories'] = self::getStories($id);

        return $res;
    }

    public static function getById($id)
    {
        return Story::find($id);
    }

    public static function delById($id)
    {
        return Cache::forget(self::STORY_KEY.'by-id:id-'.$id);
    }

    public static function getStories($id, $pid = null)
    {
        $stories = Story::whereProfileId($id)
            ->where('active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        if ($stories->isEmpty()) {
            return [];
        }

        $seen = $pid
            ? self::seenLookup((int) $pid, (int) $id, $stories->pluck('id')->all())
            : [];

        return $stories
            ->map(function ($s) use ($seen) {
                return [
                    'id' => (string) $s->id,
                    'type' => $s->type,
                    'duration' => 10,
                    'seen' => isset($seen[$s->id]),
                    'created_at' => $s->created_at->toAtomString(),
                    'expires_at' => $s->expires_at->toAtomString(),
                    'media' => url(Storage::url($s->path)),
                    'can_reply' => (bool) $s->can_reply,
                    'can_react' => (bool) $s->can_react,
                    'poll' => null,
                ];
            })
            ->toArray();
    }

    private static function seenLookup(int $viewerId, int $authorId, array $storyIds): array
    {
        $seen = app(StoryIndexService::class)->seenStoryIds($viewerId, $authorId);

        if (empty($seen)) {
            $seen = StoryView::whereProfileId($viewerId)
                ->whereIn('story_id', $storyIds)
                ->pluck('story_id')
                ->all();
        }

        return array_flip($seen);
    }

    public static function views($id)
    {
        return StoryView::whereStoryId($id)
            ->pluck('profile_id')
            ->toArray();
    }

    public static function hasSeen($pid, $sid)
    {
        if (app(StoryIndexService::class)->hasSeen((int) $pid, (int) $sid)) {
            return true;
        }

        $key = self::STORY_KEY.'seen:'.$pid.':'.$sid;

        return Cache::remember($key, 3600, function () use ($pid, $sid) {
            return StoryView::whereStoryId($sid)
                ->whereProfileId($pid)
                ->exists();
        });
    }

    public static function latest($pid)
    {
        $id = app(StoryIndexService::class)->latestStoryId((int) $pid);
        if ($id) {
            return $id;
        }

        return Cache::remember(self::STORY_KEY.'latest:pid-'.$pid, 3600, function () use ($pid) {
            $story = Story::whereProfileId($pid)
                ->where('active', true)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            return $story ? $story->id : null;
        });
    }

    public static function delLatest($pid)
    {
        Cache::forget(self::STORY_KEY.'latest:pid-'.$pid);
        AccountService::del($pid);

        return Cache::forget('pf:stories:recent-self:'.$pid);
    }

    public static function addSeen($pid, $sid)
    {
        return Cache::put(self::STORY_KEY.'seen:'.$pid.':'.$sid, true, 86400);
    }

    public static function adminStats()
    {
        return Cache::remember('pf:admin:stories:stats', 300, function () {
            $total = Story::count();

            return [
                'active' => [
                    'today' => Story::whereDate('created_at', now()->today())->count(),
                    'month' => Story::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ],
                'total' => $total,
                'remote' => [
                    'today' => Story::whereLocal(false)->whereDate('created_at', now()->today())->count(),
                    'month' => Story::whereLocal(false)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ],
                'storage' => [
                    'sum' => (int) Story::sum('size'),
                    'average' => (int) Story::avg('size'),
                ],
                'avg_spu' => $total ? (int) ($total / Story::distinct()->count('profile_id')) : 'N/A',
                'avg_duration' => (int) floor(Story::avg('duration')),
                'avg_type' => $total ? Story::selectRaw('type, count(id) as count')->groupBy('type')->orderByDesc('count')->first()->type : 'N/A',
            ];
        });
    }

    public static function rotateQueue()
    {
        return Redis::smembers('pf:stories:rotate-queue');
    }

    public static function addRotateQueue($id)
    {
        return Redis::sadd('pf:stories:rotate-queue', $id);
    }

    public static function removeRotateQueue($id)
    {
        self::delById($id);

        return Redis::srem('pf:stories:rotate-queue', $id);
    }

    public static function reactIncrement($storyId, $profileId)
    {
        $key = 'pf:stories:react-counter:storyid-'.$storyId.':profileid-'.$profileId;

        $count = (int) Redis::incr($key);
        if ($count === 1) {
            Redis::expire($key, 86400);
        }

        return $count;
    }

    public static function reactCounter($storyId, $profileId)
    {
        $key = 'pf:stories:react-counter:storyid-'.$storyId.':profileid-'.$profileId;

        return (int) (Redis::get($key) ?? 0);
    }
}
