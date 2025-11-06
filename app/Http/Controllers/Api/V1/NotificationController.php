<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\NotificationPipeline\NotificationWarmUserCache;
use App\Notification;
use App\Services\NotificationService;
use App\Transformer\Api\Mastodon\v1\NotificationTransformer;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class NotificationController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v1/notifications
     *
     * @return NotificationTransformer
     */
    public function accountNotifications(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:80',
            'max_id' => 'nullable|integer|min:1',
            'since_id' => 'nullable|integer|min:1',
            'min_id' => 'nullable|integer|min:1',
            'types' => 'nullable|array',
            'exclude_types' => 'nullable|array',
        ]);

        $pid = $request->user()->profile_id;
        $limit = $request->input('limit', 20);
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');
        $min_id = $request->input('min_id');
        $types = $request->input('types');
        $excludeTypes = $request->input('exclude_types');

        if ($limit > 80) {
            $limit = 80;
        }

        if ($max_id || $since_id || $min_id) {
            $res = NotificationService::getMaxId($pid, $limit, $max_id, $since_id, $min_id);
        } else {
            $res = NotificationService::get($pid, $limit);
        }

        if (empty($res) && ($max_id || $since_id || $min_id)) {
            $res = NotificationService::get($pid, $limit);
        }

        if (empty($res)) {
            NotificationWarmUserCache::dispatch($pid);

            return $this->json([]);
        }

        $res = collect($res)
            ->filter(function ($n) use ($types, $excludeTypes) {
                if ($types && is_array($types) && ! empty($types)) {
                    return in_array($n['type'], $types);
                }

                if ($excludeTypes && is_array($excludeTypes) && ! empty($excludeTypes)) {
                    return ! in_array($n['type'], $excludeTypes);
                }

                return true;
            })
            ->values();

        return $this->json($res);
    }
}