<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Http\Controllers\Controller;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Services\BouncerService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Status;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ModerationController extends Controller
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

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function moderatePost(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_if($request->user()->is_admin != true, 403);
        abort_unless($request->user()->tokenCan('admin:write'), 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $this->validate($request, [
            'action' => 'required|in:cw,mark-public,mark-unlisted,mark-private,mark-spammer,delete',
        ]);

        $action = $request->input('action');
        $status = Status::find($id);

        if (! $status) {
            return response()->json(['error' => 'Cannot find status'], 400);
        }

        if ($status->uri == null) {
            if ($status->profile->user && $status->profile->user->is_admin) {
                return response()->json(['error' => 'Cannot moderate admin accounts'], 400);
            }
        }

        if ($action == 'mark-spammer') {
            $status->profile->update([
                'unlisted' => true,
                'cw' => true,
                'no_autolink' => true,
            ]);

            Status::whereProfileId($status->profile_id)
                ->get()
                ->each(function ($s) {
                    if (in_array($s->scope, ['public', 'unlisted'])) {
                        $s->scope = 'private';
                        $s->visibility = 'private';
                    }
                    $s->is_nsfw = true;
                    $s->save();
                    StatusService::del($s->id, true);
                });

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$status->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$status->profile_id);
            Cache::forget('admin-dash:reports:spam-count');
        } elseif ($action == 'cw') {
            $state = $status->is_nsfw;
            $status->is_nsfw = ! $state;
            $status->save();
            StatusService::del($status->id);
        } elseif ($action == 'mark-public') {
            $state = $status->scope;
            $status->scope = 'public';
            $status->visibility = 'public';
            $status->save();
            StatusService::del($status->id, true);
            if ($state !== 'public') {
                if ($status->in_reply_to_id == null && $status->reblog_of_id == null) {
                    NetworkTimelineService::add($status->id);
                } else {
                    if ($status->in_reply_to_id == null && $status->reblog_of_id == null) {
                        PublicTimelineService::add($status->id);
                    }
                }
            }
        } elseif ($action == 'mark-unlisted') {
            $state = $status->scope;
            $status->scope = 'unlisted';
            $status->visibility = 'unlisted';
            $status->save();
            StatusService::del($status->id);
            if ($state == 'public') {
                PublicTimelineService::del($status->id);
                NetworkTimelineService::del($status->id);
            }
        } elseif ($action == 'mark-private') {
            $state = $status->scope;
            $status->scope = 'private';
            $status->visibility = 'private';
            $status->save();
            StatusService::del($status->id);
            if ($state == 'public') {
                PublicTimelineService::del($status->id);
                NetworkTimelineService::del($status->id);
            }
        } elseif ($action == 'delete') {
            PublicTimelineService::del($status->id);
            NetworkTimelineService::del($status->id);
            Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
            Cache::forget('profile:status_count:'.$status->profile_id);
            Cache::forget('profile:embed:'.$status->profile_id);
            StatusService::del($status->id, true);
            Cache::forget('profile:status_count:'.$status->profile_id);
            $status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);

            return [];
        }

        Cache::forget('_api:statuses:recent_9:'.$status->profile_id);

        return StatusService::get($status->id, false);
    }
}