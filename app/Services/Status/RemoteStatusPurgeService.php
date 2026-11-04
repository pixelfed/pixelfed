<?php

namespace App\Services\Status;

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Media;
use App\Models\Status;
use App\Services\MediaService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class RemoteStatusPurgeService
{
    public const SNOWFLAKE_EPOCH_MS = 1549756800000;

    public const STATUS_MORPHS = ['App\Status', Status::class];

    protected array $moderationIds = [];

    protected bool $dryRun = false;

    protected bool $skipStoredMedia = false;

    protected array $tableExists = [];

    public function __construct(bool $dryRun = false, bool $skipStoredMedia = false)
    {
        $this->dryRun = $dryRun;
        $this->skipStoredMedia = $skipStoredMedia;
    }

    public static function maxIdForCutoff(Carbon $cutoff): int
    {
        $ms = (int) $cutoff->copy()->utc()->getTimestampMs() - self::SNOWFLAKE_EPOCH_MS;

        return $ms > 0 ? $ms << 22 : 0;
    }

    public function loadModerationIds(): int
    {
        $ids = [];

        DB::table('reports')
            ->whereIn('object_type', self::STATUS_MORPHS)
            ->select('id', 'object_id')
            ->chunkById(5000, function ($rows) use (&$ids) {
                foreach ($rows as $row) {
                    $ids[(int) $row->object_id] = true;
                }
            });

        DB::table('account_interstitials')
            ->whereIn('item_type', self::STATUS_MORPHS)
            ->whereNotNull('item_id')
            ->select('id', 'item_id')
            ->chunkById(5000, function ($rows) use (&$ids) {
                foreach ($rows as $row) {
                    $ids[(int) $row->item_id] = true;
                }
            });

        if ($this->hasTable('remote_reports')) {
            DB::table('remote_reports')
                ->whereNotNull('status_ids')
                ->select('id', 'status_ids')
                ->chunkById(1000, function ($rows) use (&$ids) {
                    foreach ($rows as $row) {
                        $decoded = json_decode((string) $row->status_ids, true);
                        if (! is_array($decoded)) {
                            continue;
                        }
                        foreach ($decoded as $statusId) {
                            if (is_numeric($statusId)) {
                                $ids[(int) $statusId] = true;
                            }
                        }
                    }
                });
        }

        $this->moderationIds = $ids;

        return count($ids);
    }

    public function processWindow(int $cursor, int $batch): array
    {
        $result = [
            'next_cursor' => null,
            'scanned' => 0,
            'candidates' => 0,
            'deleted' => 0,
            'cascaded' => 0,
            'kept' => 0,
            'media_jobs' => 0,
        ];

        $rows = DB::table('statuses')
            ->select('id', 'profile_id', 'in_reply_to_id', 'reblog_of_id', 'type', 'deleted_at', 'pinned_order')
            ->where('id', '<', $cursor)
            ->orderByDesc('id')
            ->limit($batch)
            ->get();

        if ($rows->isEmpty()) {
            return $result;
        }

        $result['scanned'] = $rows->count();
        $result['next_cursor'] = (int) $rows->last()->id;

        $candidates = $this->remoteOnly($rows);
        $result['candidates'] = count($candidates);

        if ($candidates === []) {
            return $result;
        }

        $keep = $this->keepSet($candidates);

        $purge = [];
        foreach ($candidates as $id => $row) {
            if (! isset($keep[$id])) {
                $purge[$id] = $row;
            }
        }

        $result['kept'] = count($candidates) - count($purge);

        if ($purge === [] || $this->dryRun) {
            $result['deleted'] = count($purge);

            return $result;
        }

        [$deleted, $cascaded, $mediaJobs] = $this->purge($purge);

        $result['deleted'] = $deleted;
        $result['cascaded'] = $cascaded;
        $result['media_jobs'] = $mediaJobs;

        return $result;
    }

    protected function remoteOnly($rows): array
    {
        $profileIds = $rows->pluck('profile_id')->filter()->unique()->values()->all();

        if ($profileIds === []) {
            return [];
        }

        $remote = DB::table('profiles')
            ->whereIn('id', $profileIds)
            ->whereNotNull('domain')
            ->whereNull('user_id')
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $candidates = [];
        foreach ($rows as $row) {
            if ($row->profile_id && isset($remote[(int) $row->profile_id])) {
                $candidates[(int) $row->id] = $row;
            }
        }

        return $candidates;
    }

    protected function keepSet(array $candidates): array
    {
        $allIds = array_keys($candidates);
        $liveIds = [];
        foreach ($candidates as $id => $row) {
            if ($row->deleted_at === null) {
                $liveIds[] = $id;
            }
        }

        $keep = [];

        foreach ($allIds as $id) {
            if (isset($this->moderationIds[$id])) {
                $keep[$id] = true;
            }
        }

        $this->keepWhereIn($keep, 'direct_messages', 'status_id', $allIds);

        if ($this->hasTable('dm_messages')) {
            $this->keepWhereIn($keep, 'dm_messages', 'status_id', $allIds);
        }

        if ($this->skipStoredMedia) {
            $stored = DB::table('media')
                ->whereIn('status_id', $allIds)
                ->whereNotNull('media_path')
                ->where('media_path', 'not like', 'http%')
                ->distinct()
                ->pluck('status_id');
            foreach ($stored as $id) {
                $keep[(int) $id] = true;
            }
        }

        if ($liveIds === []) {
            return $keep;
        }

        foreach ($liveIds as $id) {
            if ($candidates[$id]->pinned_order !== null) {
                $keep[$id] = true;
            }
        }

        $this->keepLocalWhereIn($keep, 'likes', 'status_id', 'profile_id', $liveIds, true);
        $this->keepLocalWhereIn($keep, 'mentions', 'status_id', 'profile_id', $liveIds, true);
        $this->keepLocalWhereIn($keep, 'media_tags', 'status_id', 'profile_id', $liveIds);
        $this->keepLocalWhereIn($keep, 'poll_votes', 'status_id', 'profile_id', $liveIds);
        $this->keepWhereIn($keep, 'bookmarks', 'status_id', $liveIds);
        $this->keepWhereIn($keep, 'status_archiveds', 'status_id', $liveIds);

        $collected = DB::table('collection_items')
            ->whereIn('object_id', $liveIds)
            ->distinct()
            ->pluck('object_id');
        foreach ($collected as $id) {
            $keep[(int) $id] = true;
        }

        $parentIds = [];
        foreach ($liveIds as $id) {
            $row = $candidates[$id];
            if ($row->in_reply_to_id) {
                $parentIds[(int) $row->in_reply_to_id] = true;
            }
            if ($row->reblog_of_id) {
                $parentIds[(int) $row->reblog_of_id] = true;
            }
        }

        if ($parentIds !== []) {
            $localParents = DB::table('statuses')
                ->join('profiles', 'profiles.id', '=', 'statuses.profile_id')
                ->whereIn('statuses.id', array_keys($parentIds))
                ->whereNull('profiles.domain')
                ->pluck('statuses.id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all();

            foreach ($liveIds as $id) {
                $row = $candidates[$id];
                if (($row->in_reply_to_id && isset($localParents[(int) $row->in_reply_to_id])) ||
                    ($row->reblog_of_id && isset($localParents[(int) $row->reblog_of_id]))) {
                    $keep[$id] = true;
                }
            }
        }

        $children = [];
        foreach (['in_reply_to_id', 'reblog_of_id'] as $column) {
            $rows = DB::table('statuses')
                ->select('id', $column.' as parent_id')
                ->whereIn($column, $liveIds)
                ->whereNull('deleted_at')
                ->get();
            foreach ($rows as $child) {
                $children[] = [(int) $child->id, (int) $child->parent_id];
            }
        }

        if ($children !== []) {
            do {
                $changed = false;
                foreach ($children as [$childId, $parentId]) {
                    if (isset($keep[$parentId])) {
                        continue;
                    }
                    $childPurged = isset($candidates[$childId]) && ! isset($keep[$childId]);
                    if (! $childPurged) {
                        $keep[$parentId] = true;
                        $changed = true;
                    }
                }
            } while ($changed);
        }

        return $keep;
    }

    protected function keepWhereIn(array &$keep, string $table, string $column, array $ids): void
    {
        $found = DB::table($table)->whereIn($column, $ids)->distinct()->pluck($column);
        foreach ($found as $id) {
            $keep[(int) $id] = true;
        }
    }

    protected function keepLocalWhereIn(array &$keep, string $table, string $column, string $profileColumn, array $ids, bool $softDeletes = false): void
    {
        $found = DB::table($table)
            ->join('profiles', 'profiles.id', '=', $table.'.'.$profileColumn)
            ->whereIn($table.'.'.$column, $ids)
            ->whereNull('profiles.domain')
            ->when($softDeletes, fn ($q) => $q->whereNull($table.'.deleted_at'))
            ->distinct()
            ->pluck($table.'.'.$column);

        foreach ($found as $id) {
            $keep[(int) $id] = true;
        }
    }

    protected function purge(array $purge): array
    {
        $ids = array_keys($purge);

        $shareIds = DB::table('statuses')
            ->join('profiles', 'profiles.id', '=', 'statuses.profile_id')
            ->whereIn('statuses.reblog_of_id', $ids)
            ->where(function ($q) {
                $q->whereNotNull('statuses.deleted_at')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('profiles.domain')->whereNull('profiles.user_id');
                    });
            })
            ->pluck('statuses.id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => isset($purge[$id]) || isset($this->moderationIds[$id]))
            ->values()
            ->all();

        $allIds = array_merge($ids, $shareIds);
        $allSet = array_fill_keys($allIds, true);

        $replyDecrements = [];
        $reblogDecrements = [];
        foreach ($purge as $row) {
            if ($row->deleted_at !== null) {
                continue;
            }
            if ($row->in_reply_to_id && ! isset($allSet[(int) $row->in_reply_to_id])) {
                $parent = (int) $row->in_reply_to_id;
                $replyDecrements[$parent] = ($replyDecrements[$parent] ?? 0) + 1;
            }
            if ($row->reblog_of_id && ! isset($allSet[(int) $row->reblog_of_id])) {
                $parent = (int) $row->reblog_of_id;
                $reblogDecrements[$parent] = ($reblogDecrements[$parent] ?? 0) + 1;
            }
        }

        $hashtagDecrements = DB::table('status_hashtags')
            ->whereIn('status_id', $allIds)
            ->select('hashtag_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('hashtag_id')
            ->pluck('aggregate', 'hashtag_id')
            ->map(fn ($c) => (int) $c)
            ->all();

        $media = DB::table('media')
            ->whereIn('status_id', $allIds)
            ->select('id', 'media_path')
            ->get();

        $storedMediaIds = [];
        $plainMediaIds = [];
        foreach ($media as $m) {
            if ($m->media_path && ! str_starts_with((string) $m->media_path, 'http')) {
                $storedMediaIds[] = (int) $m->id;
            } else {
                $plainMediaIds[] = (int) $m->id;
            }
        }

        $pollIds = DB::table('polls')->whereIn('status_id', $allIds)->pluck('id')->all();

        DB::transaction(function () use ($allIds, $plainMediaIds, $storedMediaIds, $pollIds, $replyDecrements, $reblogDecrements, $hashtagDecrements) {
            foreach (['likes', 'mentions', 'bookmarks', 'status_hashtags', 'status_views', 'status_edits', 'media_tags', 'status_archiveds'] as $table) {
                DB::table($table)->whereIn('status_id', $allIds)->delete();
            }

            if ($pollIds !== []) {
                DB::table('poll_votes')->whereIn('poll_id', $pollIds)->delete();
                DB::table('polls')->whereIn('id', $pollIds)->delete();
            }

            if ($this->hasTable('custom_filter_statuses')) {
                DB::table('custom_filter_statuses')->whereIn('status_id', $allIds)->delete();
            }

            if ($plainMediaIds !== []) {
                DB::table('media')->whereIn('id', $plainMediaIds)->delete();
            }

            if ($storedMediaIds !== []) {
                DB::table('media')->whereIn('id', $storedMediaIds)->update(['status_id' => null]);
            }

            $this->decrement('statuses', 'reply_count', $replyDecrements);
            $this->decrement('statuses', 'reblogs_count', $reblogDecrements);
            $this->decrement('hashtags', 'cached_count', $hashtagDecrements);

            DB::table('statuses')->whereIn('id', $allIds)->delete();
        });

        if ($storedMediaIds !== []) {
            Media::whereIn('id', $storedMediaIds)->get()->each(function ($m) {
                MediaDeletePipeline::dispatch($m)->onQueue('mmo');
            });
        }

        $this->forgetCaches($allIds);

        $parents = array_keys($replyDecrements + $reblogDecrements);
        foreach ($parents as $parentId) {
            StatusService::del($parentId);
        }

        return [count($ids), count($shareIds), count($storedMediaIds)];
    }

    protected function decrement(string $table, string $column, array $amounts): void
    {
        if ($amounts === []) {
            return;
        }

        $byAmount = [];
        foreach ($amounts as $id => $amount) {
            $byAmount[$amount][] = $id;
        }

        $wrapped = DB::getQueryGrammar()->wrap($column);

        foreach ($byAmount as $amount => $ids) {
            $amount = (int) $amount;
            foreach (array_chunk($ids, 1000) as $chunk) {
                DB::table($table)
                    ->whereIn('id', $chunk)
                    ->whereNotNull($column)
                    ->update([
                        $column => DB::raw("CASE WHEN {$wrapped} > {$amount} THEN {$wrapped} - {$amount} ELSE 0 END"),
                    ]);
            }
        }
    }

    protected function forgetCaches(array $ids): void
    {
        $keys = [];
        foreach ($ids as $id) {
            $keys[] = StatusService::key($id, true);
            $keys[] = StatusService::key($id, false);
            $keys[] = MediaService::CACHE_KEY.$id;
            $keys[] = 'pf:services:sh:id:'.$id;
            $keys[] = 'pf:status:ap:v1:sid:'.$id;
        }

        try {
            $store = Cache::getStore();

            if ($store instanceof RedisStore) {
                $prefix = $store->getPrefix();
                $connection = $store->connection();
                foreach (array_chunk($keys, 500) as $chunk) {
                    $connection->del(...array_map(fn ($key) => $prefix.$key, $chunk));
                }
            } else {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }

            Redis::zrem(NetworkTimelineService::CACHE_KEY, ...$ids);
            Redis::zrem(PublicTimelineService::CACHE_KEY, ...$ids);
        } catch (\Throwable $e) {
            Log::warning('RemoteStatusPurgeService: cache cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function hasTable(string $table): bool
    {
        return $this->tableExists[$table] ??= Schema::hasTable($table);
    }
}
