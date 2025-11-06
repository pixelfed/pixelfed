<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class StoryIndexService
{
    public const STORY_TTL = 86400;

    private const REBUILD_LOCK_TTL = 300;

    private function authorKey($authorId)
    {
        return "story:by_author:{$authorId}";
    }

    private function storyKey($storyId)
    {
        return "story:{$storyId}";
    }

    private function seenKey($viewer, $author)
    {
        return "story:seen:{$viewer}:{$author}";
    }

    private function rebuildLockKey()
    {
        return 'story:rebuilding';
    }

    /**
     * Safely convert Redis result to integer, handling both predis and phpredis
     */
    private function redisToInt($value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) || is_numeric($value)) {
            return (int) $value;
        }

        // Handle phpredis object returns
        if (is_object($value) && method_exists($value, '__toString')) {
            return (int) $value->__toString();
        }

        // Fallback for unexpected types
        return 0;
    }

    /**
     * Safely convert Redis result to boolean
     */
    private function redisToBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value > 0;
        }

        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }

        // Handle phpredis object returns
        if (is_object($value) && method_exists($value, '__toString')) {
            $str = $value->__toString();

            return $str === '1' || strtolower($str) === 'true';
        }

        return false;
    }

    /**
     * Safely execute Redis commands that return integers
     */
    private function redisInt(callable $command): int
    {
        return $this->redisToInt($command());
    }

    /**
     * Safely execute Redis commands that return booleans
     */
    private function redisBool(callable $command): bool
    {
        return $this->redisToBool($command());
    }

    /**
     * Safely convert Redis result to array, handling both predis and phpredis
     */
    private function redisToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_null($value)) {
            return [];
        }

        // Handle phpredis object returns that might be iterable
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            if ($value instanceof \Iterator || $value instanceof \IteratorAggregate) {
                return iterator_to_array($value);
            }

            if (method_exists($value, '__toString')) {
                $str = $value->__toString();

                return $str ? [$str] : [];
            }
        }

        // Handle single values
        if (is_string($value) || is_numeric($value)) {
            return [$value];
        }

        return [];
    }

    /**
     * Safely execute Redis commands that return arrays
     */
    private function redisArray(callable $command): array
    {
        return $this->redisToArray($command());
    }

    public function indexStory($story): void
    {
        if (! $story->active) {
            $this->removeStory($story->id, $story->profile_id);

            return;
        }

        $author = (string) $story->profile_id;
        $sid = (string) $story->id;
        $createdAt = $story->created_at;
        $score = $createdAt->getTimestamp();
        $expiresAt = $story->expires_at;
        $ttl = (int) max(0, $expiresAt->getTimestamp() - time());

        if ($ttl <= 0) {
            $this->removeStory($sid, $author);

            return;
        }

        $score = (float) $createdAt->getTimestamp();
        $duration = (int) ($story->duration ?? 0);
        $overlays = $story->story ? json_encode(data_get($story->story, 'overlays', [])) : '[]';
        $viewCount = (int) ($story->view_count ?? 0);
        $createdIso = $createdAt->toIso8601String();
        $type = $story->type;
        $path = $story->path;

        Redis::pipeline(function ($pipe) use (
            $author, $sid, $score, $ttl, $duration, $overlays, $viewCount, $createdIso, $type, $path
        ) {
            $keyStory = $this->storyKey($sid);
            $keyAuth = $this->authorKey($author);

            $pipe->hset($keyStory, 'id', $sid);
            $pipe->hset($keyStory, 'profile_id', (string) $author);
            $pipe->hset($keyStory, 'type', (string) $type);
            $pipe->hset($keyStory, 'path', (string) $path);
            $pipe->hset($keyStory, 'duration', (string) $duration);
            $pipe->hset($keyStory, 'overlays', $overlays);
            $pipe->hset($keyStory, 'created_at', $createdIso);
            $pipe->hset($keyStory, 'view_count', (string) $viewCount);
            $pipe->expire($keyStory, (int) $ttl);

            if (config('database.redis.client') === 'predis') {
                $pipe->zadd($keyAuth, [$sid => $score]);
            } else {
                $pipe->zadd($keyAuth, $score, $sid);
            }
            $pipe->sadd('story:active_authors', $author);
            $pipe->expire($keyAuth, (int) ($ttl + 3600));
        });
    }

    public function removeStory(string|int $storyId, string|int $authorId): void
    {
        $sid = (string) $storyId;
        $aid = (string) $authorId;

        Redis::pipeline(function ($pipe) use ($sid, $aid) {
            $pipe->zrem($this->authorKey($aid), $sid);
            $pipe->del($this->storyKey($sid));
        });

        if ($this->redisInt(fn () => Redis::zcard($this->authorKey($aid))) === 0) {
            Redis::srem('story:active_authors', $aid);
        }
    }

    public function markSeen(int $viewerId, int $authorId, int $storyId, \DateTimeInterface $storyCreatedAt): void
    {
        $key = $this->seenKey($viewerId, $authorId);
        Redis::sadd($key, (string) $storyId);

        $expiresAt = now()->parse($storyCreatedAt)->addSeconds(self::STORY_TTL);
        $secondsUntilExpiry = $expiresAt->getTimestamp() - time();
        $ttl = max(1, $secondsUntilExpiry);

        $currentTtl = $this->redisInt(fn () => Redis::ttl($key));
        if ($currentTtl < 0) {
            $currentTtl = 0;
        }

        $finalTtl = max($ttl, $currentTtl);

        Redis::expire($key, $finalTtl);
    }

    public function rebuildIndex(): array
    {
        $lockKey = $this->rebuildLockKey();

        $lockAcquired = Redis::setnx($lockKey, '1');
        if ($lockAcquired) {
            Redis::expire($lockKey, self::REBUILD_LOCK_TTL);
        }

        if (! $lockAcquired) {
            return ['status' => 'already_rebuilding', 'message' => 'Index rebuild already in progress'];
        }

        try {
            $stats = ['indexed' => 0, 'expired' => 0, 'errors' => 0, 'seen_rebuilt' => 0, 'seen_errors' => 0];

            $this->clearStoryCache();

            DB::table('stories')
                ->where('active', true)
                ->where('expires_at', '>', now())
                ->orderBy('profile_id')
                ->orderBy('created_at', 'desc')
                ->chunk(500, function ($stories) use (&$stats) {
                    foreach ($stories as $story) {
                        try {
                            $storyObj = (object) [
                                'id' => $story->id,
                                'profile_id' => $story->profile_id,
                                'active' => $story->active,
                                'created_at' => now()->parse($story->created_at),
                                'expires_at' => now()->parse($story->expires_at),
                                'duration' => $story->duration,
                                'story' => $story->story ? json_decode($story->story, true) : null,
                                'view_count' => $story->view_count ?? 0,
                                'type' => $story->type,
                                'path' => $story->path,
                            ];

                            $this->indexStory($storyObj);
                            $stats['indexed']++;
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            if (config('app.dev_log')) {
                                Log::error('Failed to index story during rebuild', [
                                    'story_id' => $story->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                });

            $cutoffDate = now()->subHours(48);

            DB::table('story_views')
                ->join('stories', 'story_views.story_id', '=', 'stories.id')
                ->where('story_views.created_at', '>=', $cutoffDate)
                ->where('stories.active', true)
                ->where('stories.expires_at', '>', now())
                ->select(
                    'story_views.profile_id as viewer_id',
                    'story_views.story_id',
                    'stories.profile_id as author_id',
                    'stories.created_at as story_created_at',
                    'story_views.created_at as view_created_at'
                )
                ->orderBy('story_views.id')
                ->chunk(1000, function ($views) use (&$stats) {
                    foreach ($views as $view) {
                        try {
                            $this->markSeen(
                                (int) $view->viewer_id,
                                (int) $view->author_id,
                                (int) $view->story_id,
                                now()->parse($view->story_created_at)
                            );
                            $stats['seen_rebuilt']++;
                        } catch (\Throwable $e) {
                            $stats['seen_errors']++;
                            if (config('app.dev_log')) {
                                Log::error('Failed to rebuild seen data during cache rebuild', [
                                    'viewer_id' => $view->viewer_id,
                                    'story_id' => $view->story_id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                });

            return [
                'status' => 'success',
                'message' => 'Story index and seen data rebuilt successfully',
                'stats' => $stats,
            ];

        } finally {
            Redis::del($lockKey);
        }
    }

    private function clearStoryCache(): void
    {
        $storyKeys = $this->redisArray(fn () => Redis::keys('story:*'));
        $storyKeys = array_filter($storyKeys, function ($key) {
            return ! str_contains($key, 'following:');
        });

        if (! empty($storyKeys)) {
            $chunks = array_chunk($storyKeys, 1000);
            foreach ($chunks as $chunk) {
                Redis::del(...$chunk);
            }
        }
    }

    private function ensureStoryCacheHealth(): bool
    {
        $activeCount = $this->redisInt(fn () => Redis::scard('story:active_authors'));

        if ($activeCount > 0) {
            return true;
        }

        $dbActiveCount = DB::table('stories')
            ->where('active', true)
            ->where('expires_at', '>', now())
            ->count();

        if ($dbActiveCount === 0) {
            return true;
        }

        if (config('app.dev_log')) {
            Log::info('Story cache appears empty, triggering rebuild', [
                'db_active_stories' => $dbActiveCount,
                'redis_active_authors' => $activeCount,
            ]);
        }

        $result = $this->rebuildIndex();

        return $result['status'] === 'success';
    }

    public function fetchCarouselNodes(int $viewerId, callable $profileHydrator): array
    {
        if (! $this->ensureStoryCacheHealth()) {
            if (config('app.dev_log')) {
                Log::warning('Failed to rebuild story cache, falling back to database query');
            }

            return $this->fetchCarouselNodesFromDatabase($viewerId, $profileHydrator);
        }

        $pid = (string) $viewerId;
        $opt = $this->withScoresOpt();

        if (! Redis::exists("following:{$pid}")) {
            $this->hydrateFollowingFromSql($viewerId);
        }

        $followingCount = $this->redisInt(fn () => Redis::scard("following:{$pid}"));
        $activeCount = $this->redisInt(fn () => Redis::scard('story:active_authors'));

        $authorIds = [];

        if ($followingCount > 1500) {
            $active = $this->redisArray(fn () => Redis::smembers('story:active_authors'));
            if ($active) {
                $results = Redis::pipeline(function ($pipe) use ($active, $pid) {
                    foreach ($active as $aid) {
                        $pipe->sismember("following:{$pid}", $aid);
                    }
                });

                if (is_array($results) && count($results) === count($active)) {
                    foreach ($active as $i => $aid) {
                        if ($this->redisToBool($results[$i] ?? false)) {
                            $authorIds[] = $aid;
                        }
                    }
                } else {
                    $authorIds = array_filter($active, fn ($aid) => $this->redisBool(fn () => Redis::sismember("following:{$pid}", $aid)));
                }
            }
        } else {
            $authorIds = $this->redisArray(fn () => Redis::sinter("following:{$pid}", 'story:active_authors'));
        }

        if ($this->redisInt(fn () => Redis::zcard($this->authorKey($pid))) > 0) {
            array_unshift($authorIds, $pid);
            $authorIds = array_values(array_unique($authorIds));
        }

        if (empty($authorIds)) {
            return [];
        }

        $responses = $this->redisArray(fn () => Redis::pipeline(function ($pipe) use ($authorIds, $opt) {
            foreach ($authorIds as $aid) {
                $pipe->zrevrange("story:by_author:{$aid}", 0, -1, $opt);
            }
        }));

        $authorToStoryIds = [];
        $authorLatestTs = [];
        foreach ($authorIds as $i => $aid) {
            $withScores = $responses[$i] ?? [];
            // Ensure withScores is also an array
            $withScores = $this->redisToArray($withScores);
            $authorToStoryIds[$aid] = array_keys($withScores);
            $authorLatestTs[$aid] = $withScores ? (float) array_values($withScores)[0] : 0.0;
        }

        $allStoryIds = [];
        foreach ($authorToStoryIds as $list) {
            $allStoryIds = array_merge($allStoryIds, $list);
        }
        $allStoryIds = array_values(array_unique($allStoryIds));

        $storyMap = [];
        foreach ($allStoryIds as $sid) {
            $h = $this->redisArray(fn () => Redis::hgetall($this->storyKey($sid)));
            if (! empty($h)) {
                $storyMap[$sid] = $h;
            }
        }

        $seenCache = [];
        foreach ($authorIds as $aid) {
            $seenCache[$aid] = $this->redisArray(fn () => Redis::smembers($this->seenKey($viewerId, (int) $aid)));
            $seenCache[$aid] = array_flip($seenCache[$aid]);
        }

        // Ensure $authorIds is always an array before using array_map
        $authorIds = $this->redisToArray($authorIds);
        $profiles = $profileHydrator(array_map('intval', $authorIds));

        $nodes = [];
        foreach ($authorIds as $aid) {
            $profile = $profiles[(int) $aid] ?? null;
            if (! $profile) {
                continue;
            }

            $isAuthor = ((string) $profile['id'] === $pid);
            $storyItems = [];
            foreach (Arr::get($authorToStoryIds, $aid, []) as $sid) {
                $h = $storyMap[$sid] ?? null;
                if (! $h) {
                    continue;
                }

                $durationMs = max(((int) $h['duration']) * 1000, 10000);
                $viewed = $isAuthor ? true : isset($seenCache[$aid][$sid]);

                $storyItems[] = [
                    'id' => (string) $sid,
                    'type' => $h['type'],
                    'url' => url(Storage::url($h['path'])),
                    'overlays' => json_decode($h['overlays'] ?? '[]', true) ?: [],
                    'duration' => $durationMs,
                    'viewed' => $viewed,
                    'created_at' => $h['created_at'],
                    ...($isAuthor ? ['view_count' => (int) ($h['view_count'] ?? 0)] : []),
                ];
            }

            if (empty($storyItems)) {
                continue;
            }

            $url = ! empty($profile['local'])
                ? url("/stories/{$profile['username']}")
                : url("/i/rs/{$profile['id']}");

            $nodes[] = [
                'id' => $isAuthor ? 'self:'.$profile['id'] : 'pfs:'.$profile['id'],
                'username' => $profile['username'],
                'username_acct' => $profile['acct'],
                'profile_id' => (string) $profile['id'],
                'avatar' => $profile['avatar'],
                'is_author' => $isAuthor,
                'stories' => collect($storyItems)->sortBy('id')->values()->all(),
                'url' => $url,
                'hasViewed' => collect($storyItems)->every(fn ($s) => $s['viewed'] === true),
                '_latest_ts' => $authorLatestTs[$aid] ?? 0,
            ];
        }

        usort($nodes, function ($a, $b) {
            if ($a['is_author'] && ! $b['is_author']) {
                return -1;
            }
            if (! $a['is_author'] && $b['is_author']) {
                return 1;
            }
            if ($a['hasViewed'] !== $b['hasViewed']) {
                return $a['hasViewed'] <=> $b['hasViewed'];
            }

            return ($b['_latest_ts'] ?? 0) <=> ($a['_latest_ts'] ?? 0);
        });

        foreach ($nodes as &$n) {
            unset($n['_latest_ts']);
        }

        return $nodes;
    }

    private function fetchCarouselNodesFromDatabase(int $viewerId, callable $profileHydrator): array
    {
        $following = DB::table('followers')
            ->where('profile_id', $viewerId)
            ->pluck('following_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $authorIds = array_merge([(string) $viewerId], $following);

        $stories = DB::table('stories')
            ->whereIn('profile_id', array_map('intval', $authorIds))
            ->where('active', true)
            ->where('expires_at', '>', now())
            ->orderBy('profile_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('profile_id');

        if ($stories->isEmpty()) {
            return [];
        }

        $profiles = $profileHydrator(array_map('intval', $stories->keys()->toArray()));

        $nodes = [];
        foreach ($stories as $profileId => $profileStories) {
            $profile = $profiles[(int) $profileId] ?? null;
            if (! $profile) {
                continue;
            }

            $isAuthor = ((int) $profile['id'] === $viewerId);
            $storyItems = [];

            foreach ($profileStories as $story) {
                $durationMs = max(((int) $story->duration) * 1000, 10000);

                $storyItems[] = [
                    'id' => (string) $story->id,
                    'type' => $story->type,
                    'url' => url(Storage::url($story->path)),
                    'overlays' => json_decode($story->story ?? '{}', true)['overlays'] ?? [],
                    'duration' => $durationMs,
                    'viewed' => false,
                    'created_at' => $story->created_at,
                    ...($isAuthor ? ['view_count' => (int) ($story->view_count ?? 0)] : []),
                ];
            }

            if (empty($storyItems)) {
                continue;
            }

            $url = ! empty($profile['local'])
                ? url("/stories/{$profile['username']}")
                : url("/i/rs/{$profile['id']}");

            $nodes[] = [
                'id' => $isAuthor ? 'self:'.$profile['id'] : 'pfs:'.$profile['id'],
                'username' => $profile['username'],
                'username_acct' => $profile['acct'],
                'profile_id' => (string) $profile['id'],
                'avatar' => $profile['avatar'],
                'is_author' => $isAuthor,
                'stories' => collect($storyItems)->sortBy('id')->values()->all(),
                'url' => $url,
                'hasViewed' => false,
                '_latest_ts' => \Carbon\Carbon::parse($profileStories->first()->created_at)->timestamp,
            ];
        }

        usort($nodes, function ($a, $b) {
            if ($a['is_author'] && ! $b['is_author']) {
                return -1;
            }
            if (! $a['is_author'] && $b['is_author']) {
                return 1;
            }

            return ($b['_latest_ts'] ?? 0) <=> ($a['_latest_ts'] ?? 0);
        });

        foreach ($nodes as &$n) {
            unset($n['_latest_ts']);
        }

        return $nodes;
    }

    private function hydrateFollowingFromSql(int $viewerId): void
    {
        $followingKey = "following:{$viewerId}";
        $hasResults = false;

        DB::table('followers')
            ->where('profile_id', $viewerId)
            ->orderBy('id')
            ->chunk(1000, function ($followers) use ($followingKey, &$hasResults) {
                $hasResults = true;
                $ids = $followers->map(fn ($f) => (string) $f->following_id)->all();
                if (! empty($ids)) {
                    Redis::sadd($followingKey, ...$ids);
                }
            });

        if (! $hasResults) {
            Redis::pipeline(function ($pipe) use ($followingKey) {
                $pipe->sadd($followingKey, '__empty__');
                $pipe->srem($followingKey, '__empty__');
                $pipe->expire($followingKey, 3600);
            });
        } else {
            Redis::expire($followingKey, 43200);
        }
    }

    private function withScoresOpt()
    {
        return config('database.redis.client') === 'predis' ? ['withscores' => true] : true;
    }
}
