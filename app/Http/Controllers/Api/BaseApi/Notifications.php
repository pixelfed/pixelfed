<?php

namespace App\Http\Controllers\Api\BaseApi;

use App\Jobs\NotificationPipeline\NotificationWarmUserCache;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait Notifications
{
    public function notifications(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $pid = $request->user()->profile_id;
        $limit = $request->input('limit', 20);

        $since = $request->input('since_id');
        $min = $request->input('min_id');
        $max = $request->input('max_id');

        if (! $since && ! $min && ! $max) {
            $min = 1;
        }

        $maxId = null;
        $minId = null;

        if ($max) {
            $res = NotificationService::getMax($pid, $max, $limit);
            $ids = NotificationService::getRankedMaxId($pid, $max, $limit);
            if (! empty($ids)) {
                $maxId = max($ids);
                $minId = min($ids);
            }
        } else {
            $res = NotificationService::getMin($pid, $min ?? $since, $limit);
            $ids = NotificationService::getRankedMinId($pid, $min ?? $since, $limit);
            if (! empty($ids)) {
                $maxId = max($ids);
                $minId = min($ids);
            }
        }

        if (empty($res) && ! Cache::has('pf:services:notifications:hasSynced:'.$pid)) {
            Cache::put('pf:services:notifications:hasSynced:'.$pid, 1, 1209600);
            NotificationWarmUserCache::dispatch($pid);
        }

        $res = collect($res)
            ->filter(function ($n) {
                return isset($n['account'], $n['account']['id']);
            })
            ->values();

        return response()->json($res);
    }
}
