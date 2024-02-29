<?php

namespace App\Http\Controllers\Admin;

use App\AccountInterstitial;
use App\Http\Resources\AdminReport;
use App\Http\Resources\AdminRemoteReport;
use App\Http\Resources\AdminSpamReport;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\StoryPipeline\StoryDelete;
use App\Notification;
use App\Profile;
use App\Report;
use App\Models\RemoteReport;
use App\Services\AccountService;
use App\Services\ModLogService;
use App\Services\NetworkTimelineService;
use App\Services\NotificationService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Status;
use App\Story;
use App\User;
use Cache;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait AdminReportController
{
    public function reports(Request $request)
    {
        $filter = $request->input('filter') == 'closed' ? 'closed' : 'open';
        $page = $request->input('page') ?? 1;

        $ai = Cache::remember('admin-dash:reports:ai-count', 3600, function () {
            return AccountInterstitial::whereNotNull('appeal_requested_at')->whereNull('appeal_handled_at')->count();
        });

        $spam = Cache::remember('admin-dash:reports:spam-count', 3600, function () {
            return AccountInterstitial::whereType('post.autospam')->whereNull('appeal_handled_at')->count();
        });

        $mailVerifications = Redis::scard('email:manual');

        if ($filter == 'open' && $page == 1) {
            $reports = Cache::remember('admin-dash:reports:list-cache', 300, function () use ($filter) {
                return Report::whereHas('status')
                    ->whereHas('reportedUser')
                    ->whereHas('reporter')
                    ->orderBy('created_at', 'desc')
                    ->when($filter, function ($q, $filter) {
                        return $filter == 'open' ?
                        $q->whereNull('admin_seen') :
                        $q->whereNotNull('admin_seen');
                    })
                    ->paginate(6);
            });
        } else {
            $reports = Report::whereHas('status')
                ->whereHas('reportedUser')
                ->whereHas('reporter')
                ->orderBy('created_at', 'desc')
                ->when($filter, function ($q, $filter) {
                    return $filter == 'open' ?
                    $q->whereNull('admin_seen') :
                    $q->whereNotNull('admin_seen');
                })
                ->paginate(6);
        }

        return view('admin.reports.home', compact('reports', 'ai', 'spam', 'mailVerifications'));
    }

    public function showReport(Request $request, $id)
    {
        $report = Report::with('status')->findOrFail($id);
        if ($request->has('ref') && $request->input('ref') == 'email') {
            return redirect('/i/admin/reports?tab=report&id='.$report->id);
        }

        return view('admin.reports.show', compact('report'));
    }

    public function appeals(Request $request)
    {
        $appeals = AccountInterstitial::whereNotNull('appeal_requested_at')
            ->whereNull('appeal_handled_at')
            ->latest()
            ->paginate(6);

        return view('admin.reports.appeals', compact('appeals'));
    }

    public function showAppeal(Request $request, $id)
    {
        $appeal = AccountInterstitial::whereNotNull('appeal_requested_at')
            ->whereNull('appeal_handled_at')
            ->findOrFail($id);
        $meta = json_decode($appeal->meta);

        return view('admin.reports.show_appeal', compact('appeal', 'meta'));
    }

    public function spam(Request $request)
    {
        $this->validate($request, [
            'tab' => 'sometimes|in:home,not-spam,spam,settings,custom,exemptions',
        ]);

        $tab = $request->input('tab', 'home');

        $openCount = Cache::remember('admin-dash:reports:spam-count', 3600, function () {
            return AccountInterstitial::whereType('post.autospam')
                ->whereNull('appeal_handled_at')
                ->count();
        });

        $monthlyCount = Cache::remember('admin-dash:reports:spam-count:30d', 43200, function () {
            return AccountInterstitial::whereType('post.autospam')
                ->where('created_at', '>', now()->subMonth())
                ->count();
        });

        $totalCount = Cache::remember('admin-dash:reports:spam-count:total', 43200, function () {
            return AccountInterstitial::whereType('post.autospam')->count();
        });

        $uncategorized = Cache::remember('admin-dash:reports:spam-sync', 3600, function () {
            return AccountInterstitial::whereType('post.autospam')
                ->whereIsSpam(null)
                ->whereNotNull('appeal_handled_at')
                ->exists();
        });

        $avg = Cache::remember('admin-dash:reports:spam-count:avg', 43200, function () {
            if (config('database.default') != 'mysql') {
                return 0;
            }

            return AccountInterstitial::selectRaw('*, count(id) as counter')
                ->whereType('post.autospam')
                ->groupBy('user_id')
                ->get()
                ->avg('counter');
        });

        $avgOpen = Cache::remember('admin-dash:reports:spam-count:avgopen', 43200, function () {
            if (config('database.default') != 'mysql') {
                return '0';
            }
            $seconds = AccountInterstitial::selectRaw('DATE(created_at) AS start_date, AVG(TIME_TO_SEC(TIMEDIFF(appeal_handled_at, created_at))) AS timediff')->whereType('post.autospam')->whereNotNull('appeal_handled_at')->where('created_at', '>', now()->subMonth())->get();
            if (! $seconds) {
                return '0';
            }
            $mins = floor($seconds->avg('timediff') / 60);

            if ($mins < 60) {
                return $mins.' min(s)';
            }

            if ($mins < 2880) {
                return floor($mins / 60).' hour(s)';
            }

            return floor($mins / 60 / 24).' day(s)';
        });
        $avgCount = $totalCount && $avg ? floor($totalCount / $avg) : '0';

        if (in_array($tab, ['home', 'spam', 'not-spam'])) {
            $appeals = AccountInterstitial::whereType('post.autospam')
                ->when($tab, function ($q, $tab) {
                    switch ($tab) {
                        case 'home':
                            return $q->whereNull('appeal_handled_at');
                            break;
                        case 'spam':
                            return $q->whereIsSpam(true);
                            break;
                        case 'not-spam':
                            return $q->whereIsSpam(false);
                            break;
                    }
                })
                ->latest()
                ->paginate(6);

            if ($tab !== 'home') {
                $appeals = $appeals->appends(['tab' => $tab]);
            }
        } else {
            $appeals = new class
            {
                public function count()
                {
                    return 0;
                }

                public function render()
                {

                }
            };
        }

        return view('admin.reports.spam', compact('tab', 'appeals', 'openCount', 'monthlyCount', 'totalCount', 'avgCount', 'avgOpen', 'uncategorized'));
    }

    public function showSpam(Request $request, $id)
    {
        $appeal = AccountInterstitial::whereType('post.autospam')
            ->findOrFail($id);
        if ($request->has('ref') && $request->input('ref') == 'email') {
            return redirect('/i/admin/reports?tab=autospam&id='.$appeal->id);
        }
        $meta = json_decode($appeal->meta);

        return view('admin.reports.show_spam', compact('appeal', 'meta'));
    }

    public function fixUncategorizedSpam(Request $request)
    {
        if (Cache::get('admin-dash:reports:spam-sync-active')) {
            return redirect('/i/admin/reports/autospam');
        }

        Cache::put('admin-dash:reports:spam-sync-active', 1, 900);

        AccountInterstitial::chunk(500, function ($reports) {
            foreach ($reports as $report) {
                if ($report->item_type != 'App\Status') {
                    continue;
                }

                if ($report->type != 'post.autospam') {
                    continue;
                }

                if ($report->is_spam != null) {
                    continue;
                }

                $status = StatusService::get($report->item_id, false);
                if (! $status) {
                    return;
                }
                $scope = $status['visibility'];
                $report->is_spam = $scope == 'unlisted';
                $report->in_violation = $report->is_spam;
                $report->severity_index = 1;
                $report->save();
            }
        });

        Cache::forget('admin-dash:reports:spam-sync');

        return redirect('/i/admin/reports/autospam');
    }

    public function updateSpam(Request $request, $id)
    {
        $this->validate($request, [
            'action' => 'required|in:dismiss,approve,dismiss-all,approve-all,delete-account,mark-spammer',
        ]);

        $action = $request->input('action');
        $appeal = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->findOrFail($id);

        $meta = json_decode($appeal->meta);
        $res = ['status' => 'success'];
        $now = now();
        Cache::forget('admin-dash:reports:spam-count:total');
        Cache::forget('admin-dash:reports:spam-count:30d');

        if ($action == 'delete-account') {
            if (config('pixelfed.account_deletion') == false) {
                abort(404);
            }

            $user = User::findOrFail($appeal->user_id);
            $profile = $user->profile;

            if ($user->is_admin == true) {
                $mid = $request->user()->id;
                abort_if($user->id < $mid, 403);
            }

            $ts = now()->addMonth();
            $user->status = 'delete';
            $profile->status = 'delete';
            $user->delete_after = $ts;
            $profile->delete_after = $ts;
            $user->save();
            $profile->save();

            ModLogService::boot()
                ->objectUid($user->id)
                ->objectId($user->id)
                ->objectType('App\User::class')
                ->user($request->user())
                ->action('admin.user.delete')
                ->accessLevel('admin')
                ->save();

            Cache::forget('profiles:private');
            DeleteAccountPipeline::dispatch($user);

            return;
        }

        if ($action == 'dismiss') {
            $appeal->is_spam = true;
            $appeal->appeal_handled_at = $now;
            $appeal->save();

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
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
                });
            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        if ($action == 'mark-spammer') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->update(['appeal_handled_at' => $now, 'is_spam' => true]);

            $pro = Profile::whereUserId($appeal->user_id)->firstOrFail();

            $pro->update([
                'unlisted' => true,
                'cw' => true,
                'no_autolink' => true,
            ]);

            Status::whereProfileId($pro->id)
                ->get()
                ->each(function ($report) {
                    $status->is_nsfw = $meta->is_nsfw;
                    $status->scope = 'public';
                    $status->visibility = 'public';
                    $status->save();
                    StatusService::del($status->id, true);
                });

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');

            return $res;
        }

        $status = $appeal->status;
        $status->is_nsfw = $meta->is_nsfw;
        $status->scope = 'public';
        $status->visibility = 'public';
        $status->save();

        $appeal->is_spam = false;
        $appeal->appeal_handled_at = now();
        $appeal->save();

        StatusService::del($status->id);

        Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$appeal->user->profile_id);
        Cache::forget('pf:bouncer_v0:recent_by_pid:'.$appeal->user->profile_id);
        Cache::forget('admin-dash:reports:spam-count');

        return $res;
    }

    public function updateAppeal(Request $request, $id)
    {
        $this->validate($request, [
            'action' => 'required|in:dismiss,approve',
        ]);

        $action = $request->input('action');
        $appeal = AccountInterstitial::whereNotNull('appeal_requested_at')
            ->whereNull('appeal_handled_at')
            ->findOrFail($id);

        if ($action == 'dismiss') {
            $appeal->appeal_handled_at = now();
            $appeal->save();
            Cache::forget('admin-dash:reports:ai-count');

            return redirect('/i/admin/reports/appeals');
        }

        switch ($appeal->type) {
            case 'post.cw':
                $status = $appeal->status;
                $status->is_nsfw = false;
                $status->save();
                break;

            case 'post.unlist':
                $status = $appeal->status;
                $status->scope = 'public';
                $status->visibility = 'public';
                $status->save();
                break;

            default:
                // code...
                break;
        }

        $appeal->appeal_handled_at = now();
        $appeal->save();
        StatusService::del($status->id, true);
        Cache::forget('admin-dash:reports:ai-count');

        return redirect('/i/admin/reports/appeals');
    }

    public function updateReport(Request $request, $id)
    {
        $this->validate($request, [
            'action' => 'required|string',
        ]);

        $action = $request->input('action');

        $actions = [
            'ignore',
            'cw',
            'unlist',
            'delete',
            'shadowban',
            'ban',
        ];

        if (! in_array($action, $actions)) {
            return abort(403);
        }

        $report = Report::findOrFail($id);

        $this->handleReportAction($report, $action);
        Cache::forget('admin-dash:reports:list-cache');

        return response()->json(['msg' => 'Success']);
    }

    public function handleReportAction(Report $report, $action)
    {
        $item = $report->reported();
        $report->admin_seen = Carbon::now();

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
                Cache::forget('profiles:private');
                StatusService::del($item->id, true);
                break;

            case 'delete':
                // Todo: fire delete job
                $report->admin_seen = null;
                StatusService::del($item->id, true);
                break;

            case 'shadowban':
                // Todo: fire delete job
                $report->admin_seen = null;
                break;

            case 'ban':
                // Todo: fire delete job
                $report->admin_seen = null;
                break;

            default:
                $report->admin_seen = null;
                break;
        }

        $report->save();

        return $this;
    }

    protected function actionMap()
    {
        return [
            '1' => 'ignore',
            '2' => 'cw',
            '3' => 'unlist',
            '4' => 'delete',
            '5' => 'shadowban',
            '6' => 'ban',
        ];
    }

    public function bulkUpdateReport(Request $request)
    {
        $this->validate($request, [
            'action' => 'required|integer|min:1|max:10',
            'ids' => 'required|array',
        ]);
        $action = $this->actionMap()[$request->input('action')];
        $ids = $request->input('ids');
        $reports = Report::whereIn('id', $ids)->whereNull('admin_seen')->get();
        foreach ($reports as $report) {
            $this->handleReportAction($report, $action);
        }
        $res = [
            'message' => 'Success',
            'code' => 200,
        ];

        return response()->json($res);
    }

    public function reportMailVerifications(Request $request)
    {
        $ids = Redis::smembers('email:manual');
        $ignored = Redis::smembers('email:manual-ignored');
        $reports = [];
        if ($ids) {
            $reports = collect($ids)
                ->filter(function ($id) use ($ignored) {
                    return ! in_array($id, $ignored);
                })
                ->map(function ($id) {
                    $user = User::whereProfileId($id)->first();
                    if (! $user || $user->email_verified_at) {
                        return [];
                    }
                    $account = AccountService::get($id, true);
                    if (! $account) {
                        return [];
                    }
                    $account['email'] = $user->email;

                    return $account;
                })
                ->filter(function ($res) {
                    return $res && isset($res['id']);
                })
                ->values();
        }

        return view('admin.reports.mail_verification', compact('reports', 'ignored'));
    }

    public function reportMailVerifyIgnore(Request $request)
    {
        $id = $request->input('id');
        Redis::sadd('email:manual-ignored', $id);

        return redirect('/i/admin/reports');
    }

    public function reportMailVerifyApprove(Request $request)
    {
        $id = $request->input('id');
        $user = User::whereProfileId($id)->firstOrFail();
        Redis::srem('email:manual', $id);
        Redis::srem('email:manual-ignored', $id);
        $user->email_verified_at = now();
        $user->save();

        return redirect('/i/admin/reports');
    }

    public function reportMailVerifyClearIgnored(Request $request)
    {
        Redis::del('email:manual-ignored');

        return [200];
    }

    public function reportsStats(Request $request)
    {
        $stats = [
            'total' => Report::count(),
            'open' => Report::whereNull('admin_seen')->count(),
            'closed' => Report::whereNotNull('admin_seen')->count(),
            'autospam' => AccountInterstitial::whereType('post.autospam')->count(),
            'autospam_open' => AccountInterstitial::whereType('post.autospam')->whereNull(['appeal_handled_at'])->count(),
            'appeals' => AccountInterstitial::whereNotNull('appeal_requested_at')->whereNull('appeal_handled_at')->count(),
            'remote_open' => RemoteReport::whereNull('action_taken_at')->count(),
            'email_verification_requests' => Redis::scard('email:manual'),
        ];

        return $stats;
    }

    public function reportsApiAll(Request $request)
    {
        $filter = $request->input('filter') == 'closed' ? 'closed' : 'open';

        $reports = AdminReport::collection(
            Report::orderBy('id', 'desc')
                ->when($filter, function ($q, $filter) {
                    return $filter == 'open' ?
                    $q->whereNull('admin_seen') :
                    $q->whereNotNull('admin_seen');
                })
                ->groupBy(['id', 'object_id', 'object_type', 'profile_id'])
                ->cursorPaginate(6)
                ->withQueryString()
        );

        return $reports;
    }

    public function reportsApiRemote(Request $request)
    {
        $filter = $request->input('filter') == 'closed' ? 'closed' : 'open';

        $reports = AdminRemoteReport::collection(
            RemoteReport::orderBy('id', 'desc')
                ->when($filter, function ($q, $filter) {
                    return $filter == 'open' ?
                    $q->whereNull('action_taken_at') :
                    $q->whereNotNull('action_taken_at');
                })
                ->cursorPaginate(6)
                ->withQueryString()
        );

        return $reports;
    }

    public function reportsApiGet(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        return new AdminReport($report);
    }

    public function reportsApiHandle(Request $request)
    {
        $this->validate($request, [
            'object_id' => 'required',
            'object_type' => 'required',
            'id' => 'required',
            'action' => 'required|in:ignore,nsfw,unlist,private,delete,delete-all',
            'action_type' => 'required|in:post,profile,story',
        ]);

        $report = Report::whereObjectId($request->input('object_id'))->findOrFail($request->input('id'));

        if ($request->input('action_type') === 'profile') {
            return $this->reportsHandleProfileAction($report, $request->input('action'));
        } elseif ($request->input('action_type') === 'post') {
            return $this->reportsHandleStatusAction($report, $request->input('action'));
        } elseif ($request->input('action_type') === 'story') {
            return $this->reportsHandleStoryAction($report, $request->input('action'));
        }

        return $report;
    }

    protected function reportsHandleStoryAction($report, $action)
    {
        switch ($action) {
            case 'ignore':
                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'delete':
                $profile = Profile::find($report->reported_profile_id);
                $story = Story::whereProfileId($profile->id)->find($report->object_id);

                abort_if(! $story, 400, 'Invalid or missing story');

                $story->active = false;
                $story->save();

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($report->object_id)
                    ->objectType('App\Story::class')
                    ->user(request()->user())
                    ->action('admin.user.moderate')
                    ->metadata([
                        'action' => 'delete',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);
                StoryDelete::dispatch($story)->onQueue('story');

                return [200];
                break;

            case 'delete-all':
                $profile = Profile::find($report->reported_profile_id);
                $stories = Story::whereProfileId($profile->id)->whereActive(true)->get();

                abort_if(! $stories || ! $stories->count(), 400, 'Invalid or missing stories');

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($report->object_id)
                    ->objectType('App\Story::class')
                    ->user(request()->user())
                    ->action('admin.user.moderate')
                    ->metadata([
                        'action' => 'delete-all',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::where('reported_profile_id', $profile->id)
                    ->whereObjectType('App\Story')
                    ->whereNull('admin_seen')
                    ->update([
                        'admin_seen' => now(),
                    ]);
                $stories->each(function ($story) {
                    StoryDelete::dispatch($story)->onQueue('story');
                });

                return [200];
                break;
        }
    }

    protected function reportsHandleProfileAction($report, $action)
    {
        switch ($action) {
            case 'ignore':
                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'nsfw':
                if ($report->object_type === 'App\Profile') {
                    $profile = Profile::find($report->object_id);
                } elseif ($report->object_type === 'App\Status') {
                    $status = Status::find($report->object_id);
                    if (! $status) {
                        return [200];
                    }
                    $profile = Profile::find($status->profile_id);
                }

                if (! $profile) {
                    return;
                }

                abort_if($profile->user && $profile->user->is_admin, 400, 'Cannot moderate an admin account.');

                $profile->cw = true;
                $profile->save();

                foreach (Status::whereProfileId($profile->id)->cursor() as $status) {
                    $status->is_nsfw = true;
                    $status->save();
                    StatusService::del($status->id);
                    PublicTimelineService::rem($status->id);
                }

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($profile->id)
                    ->objectType('App\Profile::class')
                    ->user(request()->user())
                    ->action('admin.user.moderate')
                    ->metadata([
                        'action' => 'cw',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'nsfw' => true,
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'unlist':
                if ($report->object_type === 'App\Profile') {
                    $profile = Profile::find($report->object_id);
                } elseif ($report->object_type === 'App\Status') {
                    $status = Status::find($report->object_id);
                    if (! $status) {
                        return [200];
                    }
                    $profile = Profile::find($status->profile_id);
                }

                if (! $profile) {
                    return;
                }

                abort_if($profile->user && $profile->user->is_admin, 400, 'Cannot moderate an admin account.');

                $profile->unlisted = true;
                $profile->save();

                foreach (Status::whereProfileId($profile->id)->whereScope('public')->cursor() as $status) {
                    $status->scope = 'unlisted';
                    $status->visibility = 'unlisted';
                    $status->save();
                    StatusService::del($status->id);
                    PublicTimelineService::rem($status->id);
                }

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($profile->id)
                    ->objectType('App\Profile::class')
                    ->user(request()->user())
                    ->action('admin.user.moderate')
                    ->metadata([
                        'action' => 'unlisted',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'private':
                if ($report->object_type === 'App\Profile') {
                    $profile = Profile::find($report->object_id);
                } elseif ($report->object_type === 'App\Status') {
                    $status = Status::find($report->object_id);
                    if (! $status) {
                        return [200];
                    }
                    $profile = Profile::find($status->profile_id);
                }

                if (! $profile) {
                    return;
                }

                abort_if($profile->user && $profile->user->is_admin, 400, 'Cannot moderate an admin account.');

                $profile->unlisted = true;
                $profile->save();

                foreach (Status::whereProfileId($profile->id)->cursor() as $status) {
                    $status->scope = 'private';
                    $status->visibility = 'private';
                    $status->save();
                    StatusService::del($status->id);
                    PublicTimelineService::rem($status->id);
                }

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($profile->id)
                    ->objectType('App\Profile::class')
                    ->user(request()->user())
                    ->action('admin.user.moderate')
                    ->metadata([
                        'action' => 'private',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'delete':
                if (config('pixelfed.account_deletion') == false) {
                    abort(404);
                }

                if ($report->object_type === 'App\Profile') {
                    $profile = Profile::find($report->object_id);
                } elseif ($report->object_type === 'App\Status') {
                    $status = Status::find($report->object_id);
                    if (! $status) {
                        return [200];
                    }
                    $profile = Profile::find($status->profile_id);
                }

                if (! $profile) {
                    return;
                }

                abort_if($profile->user && $profile->user->is_admin, 400, 'Cannot delete an admin account.');

                $ts = now()->addMonth();

                if ($profile->user_id) {
                    $user = $profile->user;
                    abort_if($user->is_admin, 403, 'You cannot delete admin accounts.');
                    $user->status = 'delete';
                    $user->delete_after = $ts;
                    $user->save();
                }

                $profile->status = 'delete';
                $profile->delete_after = $ts;
                $profile->save();

                ModLogService::boot()
                    ->objectUid($profile->id)
                    ->objectId($profile->id)
                    ->objectType('App\Profile::class')
                    ->user(request()->user())
                    ->action('admin.user.delete')
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                if ($profile->user_id) {
                    DB::table('oauth_access_tokens')->whereUserId($user->id)->delete();
                    DB::table('oauth_auth_codes')->whereUserId($user->id)->delete();
                    $user->email = $user->id;
                    $user->password = '';
                    $user->status = 'delete';
                    $user->save();
                    $profile->status = 'delete';
                    $profile->delete_after = now()->addMonth();
                    $profile->save();
                    AccountService::del($profile->id);
                    DeleteAccountPipeline::dispatch($user)->onQueue('high');
                } else {
                    $profile->status = 'delete';
                    $profile->delete_after = now()->addMonth();
                    $profile->save();
                    AccountService::del($profile->id);
                    DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('high');
                }

                return [200];
                break;
        }
    }

    protected function reportsHandleStatusAction($report, $action)
    {
        switch ($action) {
            case 'ignore':
                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'nsfw':
                $status = Status::find($report->object_id);

                if (! $status) {
                    return [200];
                }

                abort_if($status->profile->user && $status->profile->user->is_admin, 400, 'Cannot moderate an admin account post.');
                $status->is_nsfw = true;
                $status->save();
                StatusService::del($status->id);

                ModLogService::boot()
                    ->objectUid($status->profile_id)
                    ->objectId($status->profile_id)
                    ->objectType('App\Status::class')
                    ->user(request()->user())
                    ->action('admin.status.moderate')
                    ->metadata([
                        'action' => 'cw',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'nsfw' => true,
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'private':
                $status = Status::find($report->object_id);

                if (! $status) {
                    return [200];
                }

                abort_if($status->profile->user && $status->profile->user->is_admin, 400, 'Cannot moderate an admin account post.');

                $status->scope = 'private';
                $status->visibility = 'private';
                $status->save();
                StatusService::del($status->id);
                PublicTimelineService::rem($status->id);

                ModLogService::boot()
                    ->objectUid($status->profile_id)
                    ->objectId($status->profile_id)
                    ->objectType('App\Status::class')
                    ->user(request()->user())
                    ->action('admin.status.moderate')
                    ->metadata([
                        'action' => 'private',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'unlist':
                $status = Status::find($report->object_id);

                if (! $status) {
                    return [200];
                }

                abort_if($status->profile->user && $status->profile->user->is_admin, 400, 'Cannot moderate an admin account post.');

                if ($status->scope === 'public') {
                    $status->scope = 'unlisted';
                    $status->visibility = 'unlisted';
                    $status->save();
                    StatusService::del($status->id);
                    PublicTimelineService::rem($status->id);
                }

                ModLogService::boot()
                    ->objectUid($status->profile_id)
                    ->objectId($status->profile_id)
                    ->objectType('App\Status::class')
                    ->user(request()->user())
                    ->action('admin.status.moderate')
                    ->metadata([
                        'action' => 'unlist',
                        'message' => 'Success!',
                    ])
                    ->accessLevel('admin')
                    ->save();

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;

            case 'delete':
                $status = Status::find($report->object_id);

                if (! $status) {
                    return [200];
                }

                $profile = $status->profile;

                abort_if($profile->user && $profile->user->is_admin, 400, 'Cannot delete an admin account post.');

                StatusService::del($status->id);

                if ($profile->user_id != null && $profile->domain == null) {
                    PublicTimelineService::del($status->id);
                    StatusDelete::dispatch($status)->onQueue('high');
                } else {
                    NetworkTimelineService::del($status->id);
                    RemoteStatusDelete::dispatch($status)->onQueue('high');
                }

                Report::whereObjectId($report->object_id)
                    ->whereObjectType($report->object_type)
                    ->update([
                        'admin_seen' => now(),
                    ]);

                return [200];
                break;
        }
    }

    public function reportsApiSpamAll(Request $request)
    {
        $tab = $request->input('tab', 'home');

        $appeals = AdminSpamReport::collection(
            AccountInterstitial::orderBy('id', 'desc')
                ->whereType('post.autospam')
                ->whereNull('appeal_handled_at')
                ->cursorPaginate(6)
                ->withQueryString()
        );

        return $appeals;
    }

    public function reportsApiSpamHandle(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'action' => 'required|in:mark-read,mark-not-spam,mark-all-read,mark-all-not-spam,delete-profile',
        ]);

        $action = $request->input('action');

        abort_if(
            $action === 'delete-profile' &&
            ! config('pixelfed.account_deletion'),
            404,
            "Cannot delete profile, account_deletion is disabled.\n\n Set `ACCOUNT_DELETION=true` in .env and re-cache config."
        );

        $report = AccountInterstitial::with('user')
            ->whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->findOrFail($request->input('id'));

        $this->reportsHandleSpamAction($report, $action);
        Cache::forget('admin-dash:reports:spam-count');
        Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$report->user->profile_id);
        Cache::forget('pf:bouncer_v0:recent_by_pid:'.$report->user->profile_id);

        return [$action, $report];
    }

    public function reportsHandleSpamAction($appeal, $action)
    {
        $meta = json_decode($appeal->meta);

        if ($action == 'mark-read') {
            $appeal->is_spam = true;
            $appeal->appeal_handled_at = now();
            $appeal->save();
            PublicTimelineService::del($appeal->item_id);
        }

        if ($action == 'mark-not-spam') {
            $status = $appeal->status;
            $status->is_nsfw = $meta->is_nsfw;
            $status->scope = 'public';
            $status->visibility = 'public';
            $status->save();

            $appeal->is_spam = false;
            $appeal->appeal_handled_at = now();
            $appeal->save();

            Notification::whereAction('autospam.warning')
                ->whereProfileId($appeal->user->profile_id)
                ->get()
                ->each(function ($n) use ($appeal) {
                    NotificationService::del($appeal->user->profile_id, $n->id);
                    $n->forceDelete();
                });

            StatusService::del($status->id);
            StatusService::get($status->id);
            if ($status->in_reply_to_id == null && $status->reblog_of_id == null) {
                PublicTimelineService::add($status->id);
            }
        }

        if ($action == 'mark-all-read') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->update([
                    'appeal_handled_at' => now(),
                    'is_spam' => true,
                ]);
        }

        if ($action == 'mark-all-not-spam') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
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
                        StatusService::del($status->id);
                    }
                    Notification::whereAction('autospam.warning')
                        ->whereProfileId($report->user->profile_id)
                        ->get()
                        ->each(function ($n) use ($report) {
                            NotificationService::del($report->user->profile_id, $n->id);
                            $n->forceDelete();
                        });
                });
        }

        if ($action == 'delete-profile') {
            $user = User::findOrFail($appeal->user_id);
            $profile = $user->profile;

            if ($user->is_admin == true) {
                $mid = request()->user()->id;
                abort_if($user->id < $mid, 403, 'You cannot delete an admin account.');
            }

            $ts = now()->addMonth();
            $user->status = 'delete';
            $profile->status = 'delete';
            $user->delete_after = $ts;
            $profile->delete_after = $ts;
            $user->save();
            $profile->save();

            $appeal->appeal_handled_at = now();
            $appeal->save();

            ModLogService::boot()
                ->objectUid($user->id)
                ->objectId($user->id)
                ->objectType('App\User::class')
                ->user(request()->user())
                ->action('admin.user.delete')
                ->accessLevel('admin')
                ->save();

            Cache::forget('profiles:private');
            DeleteAccountPipeline::dispatch($user);
        }
    }

    public function reportsApiSpamGet(Request $request, $id)
    {
        $report = AccountInterstitial::findOrFail($id);

        return new AdminSpamReport($report);
    }

    public function reportsApiRemoteHandle(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:remote_reports,id',
            'action' => 'required|in:mark-read,cw-posts,unlist-posts,delete-posts,private-posts,mark-all-read-by-domain,mark-all-read-by-username,cw-all-posts,private-all-posts,unlist-all-posts'
        ]);

        $report = RemoteReport::findOrFail($request->input('id'));
        $user = User::whereProfileId($report->account_id)->first();
        $ogPublicStatuses = [];
        $ogUnlistedStatuses = [];
        $ogNonCwStatuses = [];

        switch ($request->input('action')) {
            case 'mark-read':
                $report->action_taken_at = now();
                $report->save();
                break;
            case 'mark-all-read-by-domain':
                RemoteReport::whereInstanceId($report->instance_id)->update(['action_taken_at' => now()]);
                break;
            case 'cw-posts':
                $statuses = Status::find($report->status_ids);
                foreach($statuses as $status) {
                    if($report->account_id != $status->profile_id) {
                        continue;
                    }
                    if(!$status->is_nsfw) {
                        $ogNonCwStatuses[] = $status->id;
                    }
                    $status->is_nsfw = true;
                    $status->saveQuietly();
                    StatusService::del($status->id);
                }
                $report->action_taken_at = now();
                $report->save();
                break;
            case 'cw-all-posts':
                foreach(Status::whereProfileId($report->account_id)->lazyById(50, 'id') as $status) {
                    if($status->is_nsfw || $status->reblog_of_id) {
                        continue;
                    }
                    if(!$status->is_nsfw) {
                        $ogNonCwStatuses[] = $status->id;
                    }
                    $status->is_nsfw = true;
                    $status->saveQuietly();
                    StatusService::del($status->id);
                }
                break;
            case 'unlist-posts':
                $statuses = Status::find($report->status_ids);
                foreach($statuses as $status) {
                    if($report->account_id != $status->profile_id) {
                        continue;
                    }
                    if($status->scope === 'public') {
                        $ogPublicStatuses[] = $status->id;
                        $status->scope = 'unlisted';
                        $status->visibility = 'unlisted';
                        $status->saveQuietly();
                        StatusService::del($status->id);
                    }
                }
                $report->action_taken_at = now();
                $report->save();
                break;
            case 'unlist-all-posts':
                foreach(Status::whereProfileId($report->account_id)->lazyById(50, 'id') as $status) {
                    if($status->visibility !== 'public' || $status->reblog_of_id) {
                        continue;
                    }
                    $ogPublicStatuses[] = $status->id;
                    $status->visibility = 'unlisted';
                    $status->scope = 'unlisted';
                    $status->saveQuietly();
                    StatusService::del($status->id);
                }
                break;
            case 'private-posts':
                $statuses = Status::find($report->status_ids);
                foreach($statuses as $status) {
                    if($report->account_id != $status->profile_id) {
                        continue;
                    }
                    if(in_array($status->scope, ['public', 'unlisted', 'private'])) {
                        if($status->scope === 'public') {
                            $ogPublicStatuses[] = $status->id;
                        }
                        $status->scope = 'private';
                        $status->visibility = 'private';
                        $status->saveQuietly();
                        StatusService::del($status->id);
                    }
                }
                $report->action_taken_at = now();
                $report->save();
                break;
            case 'private-all-posts':
                foreach(Status::whereProfileId($report->account_id)->lazyById(50, 'id') as $status) {
                    if(!in_array($status->visibility, ['public', 'unlisted']) || $status->reblog_of_id) {
                        continue;
                    }
                    if($status->visibility === 'public') {
                        $ogPublicStatuses[] = $status->id;
                    } else if($status->visibility === 'unlisted') {
                        $ogUnlistedStatuses[] = $status->id;
                    }
                    $status->visibility = 'private';
                    $status->scope = 'private';
                    $status->saveQuietly();
                    StatusService::del($status->id);
                }
                break;
            case 'delete-posts':
                $statuses = Status::find($report->status_ids);
                foreach($statuses as $status) {
                    if($report->account_id != $status->profile_id) {
                        continue;
                    }
                    StatusDelete::dispatch($status);
                }
                $report->action_taken_at = now();
                $report->save();
                break;
            case 'mark-all-read-by-username':
                RemoteReport::whereNull('action_taken_at')->whereAccountId($report->account_id)->update(['action_taken_at' => now()]);
                break;

            default:
                abort(404);
                break;
        }

        if($ogPublicStatuses && count($ogPublicStatuses)) {
            Storage::disk('local')->put('mod-log-cache/' . $report->account_id . '/' . now()->format('Y-m-d') . '-og-public-statuses.json', json_encode($ogPublicStatuses, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }

        if($ogNonCwStatuses && count($ogNonCwStatuses)) {
            Storage::disk('local')->put('mod-log-cache/' . $report->account_id . '/' . now()->format('Y-m-d') . '-og-noncw-statuses.json', json_encode($ogNonCwStatuses, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }

        if($ogUnlistedStatuses && count($ogUnlistedStatuses)) {
            Storage::disk('local')->put('mod-log-cache/' . $report->account_id . '/' . now()->format('Y-m-d') . '-og-unlisted-statuses.json', json_encode($ogUnlistedStatuses, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }

        ModLogService::boot()
            ->user(request()->user())
            ->objectUid($user ? $user->id : null)
            ->objectId($report->id)
            ->objectType('App\Report::class')
            ->action('admin.report.moderate')
            ->metadata([
                'action' => $request->input('action'),
                'duration_active' => now()->parse($report->created_at)->diffForHumans()
            ])
            ->accessLevel('admin')
            ->save();

        if($report->status_ids) {
            foreach($report->status_ids as $sid) {
                RemoteReport::whereNull('action_taken_at')
                    ->whereJsonContains('status_ids', [$sid])
                    ->update(['action_taken_at' => now()]);
            }
        }
        return [200];
    }
}
