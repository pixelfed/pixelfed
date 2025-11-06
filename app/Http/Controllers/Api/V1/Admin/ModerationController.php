<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\AccountInterstitial;
use App\Http\Controllers\Controller;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\RemoteReport;
use App\Notification;
use App\Report;
use App\Services\AccountService;
use App\Services\ModLogService;
use App\Services\NotificationService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Status;
use Cache;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function autospam(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $appeals = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->latest()
            ->simplePaginate(6)
            ->map(function ($report) {
                $r = [
                    'id' => $report->id,
                    'type' => $report->type,
                    'item_id' => $report->item_id,
                    'item_type' => $report->item_type,
                    'created_at' => $report->created_at,
                ];
                if ($report->item_type === 'App\\Status') {
                    $status = StatusService::get($report->item_id, false);
                    if (! $status) {
                        return;
                    }

                    $r['status'] = $status;

                    if ($status['in_reply_to_id']) {
                        $r['parent'] = StatusService::get($status['in_reply_to_id'], false);
                    }
                }

                return $r;
            });

        return $appeals;
    }

    public function autospamHandle(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

        $this->validate($request, [
            'action' => 'required|in:dismiss,approve,dismiss-all,approve-all,delete-post,delete-account',
            'id' => 'required',
        ]);

        $action = $request->input('action');
        $id = $request->input('id');
        $appeal = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->findOrFail($id);
        $now = now();
        $res = ['status' => 'success'];
        $meta = json_decode($appeal->meta);
        $user = $appeal->user;
        $profile = $user->profile;

        if ($action == 'dismiss') {
            $appeal->is_spam = true;
            $appeal->appeal_handled_at = $now;
            $appeal->save();

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$profile->id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$profile->id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'delete-post') {
            $appeal->appeal_handled_at = now();
            $appeal->is_spam = true;
            $appeal->save();
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($appeal->status->id)
                ->objectType('App\Status::class')
                ->user($request->user())
                ->action('admin.status.delete')
                ->accessLevel('admin')
                ->save();
            PublicTimelineService::deleteByProfileId($profile->id);
            StatusDelete::dispatch($appeal->status)->onQueue('high');
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'delete-account') {
            abort_if($user->is_admin, 400, 'Cannot delete an admin account.');
            $appeal->appeal_handled_at = now();
            $appeal->is_spam = true;
            $appeal->save();
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\User::class')
                ->user($request->user())
                ->action('admin.user.delete')
                ->accessLevel('admin')
                ->save();
            PublicTimelineService::deleteByProfileId($profile->id);
            DeleteAccountPipeline::dispatch($appeal->user)->onQueue('high');
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'dismiss-all') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->update(['appeal_handled_at' => $now, 'is_spam' => true]);
            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'approve') {
            $status = $appeal->status;
            $status->is_nsfw = $meta->is_nsfw;
            $status->scope = 'public';
            $status->visibility = 'public';
            $status->save();

            $appeal->is_spam = false;
            $appeal->appeal_handled_at = now();
            $appeal->save();

            StatusService::del($status->id);

            Notification::whereAction('autospam.warning')
                ->whereProfileId($appeal->user->profile_id)
                ->get()
                ->each(function ($n) use ($appeal) {
                    NotificationService::del($appeal->user->profile_id, $n->id);
                    $n->forceDelete();
                });

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'approve-all') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->get()
                ->each(function ($report) use ($meta) {
                    $report->is_spam = false;
                    $report->appeal_handled_at = now();
                    $report->save();
                    $status = Status::find($report->item_id);
                    if ($status) {
                        $status->is_nsfw = $meta->is_nsfw;
                        $status->scope = 'public';
                        $status->visibility = 'public';
                        $status->save();
                        StatusService::del($status->id, true);
                    }

                    Notification::whereAction('autospam.warning')
                        ->whereProfileId($report->user->profile_id)
                        ->get()
                        ->each(function ($n) use ($report) {
                            NotificationService::del($report->user->profile_id, $n->id);
                            $n->forceDelete();
                        });
                });
            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        return $res;
    }

    public function modReports(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $reports = Report::whereNull('admin_seen')
            ->orderBy('created_at', 'desc')
            ->paginate(6)
            ->map(function ($report) {
                $r = [
                    'id' => $report->id,
                    'type' => $report->type,
                    'message' => $report->message,
                    'object_id' => $report->object_id,
                    'object_type' => $report->object_type,
                    'created_at' => $report->created_at,
                ];

                if ($report->profile_id) {
                    $r['reported_by_account'] = AccountService::get($report->profile_id, true);
                }

                if ($report->object_type === 'App\\Status') {
                    $status = StatusService::get($report->object_id, false);
                    if (! $status) {
                        return;
                    }

                    $r['status'] = $status;

                    if (isset($status['in_reply_to_id'])) {
                        $r['parent'] = StatusService::get($status['in_reply_to_id'], false);
                    }
                }

                if ($report->object_type === 'App\\Profile') {
                    $acct = AccountService::get($report->object_id, true);
                    if ($acct) {
                        $r['account'] = $acct;
                    }
                }

                return $r;
            })
            ->filter()
            ->values();

        return $reports;
    }

    public function modReportHandle(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

        $this->validate($request, [
            'action' => 'required|string',
            'id' => 'required',
        ]);

        $action = $request->input('action');
        $id = $request->input('id');

        $actions = [
            'ignore',
            'cw',
            'unlist',
        ];

        if (! in_array($action, $actions)) {
            return abort(403);
        }

        $report = Report::findOrFail($id);
        $item = $report->reported();
        $report->admin_seen = now();

        switch ($action) {
            case 'ignore':
                $report->not_interested = true;
                break;

            case 'cw':
                Cache::forget('status:thumb:'.$item->id);
                $item->is_nsfw = true;
                $item->save();
                $report->nsfw = true;
                StatusService::del($item->id, true);
                break;

            case 'unlist':
                $item->visibility = 'unlisted';
                $item->save();
                StatusService::del($item->id, true);
                break;

            default:
                $report->admin_seen = null;
                break;
        }

        $report->save();
        Cache::forget('admin-dash:reports:list-cache');
        Cache::forget('admin:dashboard:home:data:v0:15min');

        return ['success' => true];
    }
}