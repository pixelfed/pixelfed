<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\StatusPipeline\StatusDelete;
use Auth, Cache, DB;
use Carbon\Carbon;
use App\{
    AccountInterstitial,
    Instance,
    Like,
    Notification,
    Media,
    Profile,
    Report,
    Status,
    User
};
use App\Models\Conversation;
use App\Models\RemoteReport;
use App\Services\AccountService;
use App\Services\AdminStatsService;
use App\Services\ConfigCacheService;
use App\Services\InstanceService;
use App\Services\ModLogService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\PublicTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\NotificationService;
use App\Http\Resources\AdminInstance;
use App\Http\Resources\AdminUser;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\DeletePipeline\DeleteRemoteStatusPipeline;

class AdminApiController extends Controller
{
    public function supported(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        return response()->json(['supported' => true]);
    }

    public function getStats(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $res = AdminStatsService::summary();
        $res['autospam_count'] = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->count();
        return $res;
    }

    public function autospam(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $appeals = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->latest()
            ->simplePaginate(6)
            ->map(function($report) {
                $r = [
                    'id' => $report->id,
                    'type' => $report->type,
                    'item_id' => $report->item_id,
                    'item_type' => $report->item_type,
                    'created_at' => $report->created_at
                ];
                if($report->item_type === 'App\\Status') {
                    $status = StatusService::get($report->item_id, false);
                    if(!$status) {
                        return;
                    }

                    $r['status'] = $status;

                    if($status['in_reply_to_id']) {
                        $r['parent'] = StatusService::get($status['in_reply_to_id'], false);
                    }
                }
                return $r;
            });

        return $appeals;
    }

    public function autospamHandle(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'action' => 'required|in:dismiss,approve,dismiss-all,approve-all,delete-post,delete-account',
            'id' => 'required'
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

        if($action == 'dismiss') {
            $appeal->is_spam = true;
            $appeal->appeal_handled_at = $now;
            $appeal->save();

            Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $profile->id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:' . $profile->id);
            Cache::forget('admin-dash:reports:spam-count');
            return $res;
        }

        if($action == 'delete-post') {
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

        if($action == 'delete-account') {
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

        if($action == 'dismiss-all') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->update(['appeal_handled_at' => $now, 'is_spam' => true]);
            Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:' . $appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');
            return $res;
        }

        if($action == 'approve') {
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
                ->each(function($n) use($appeal) {
                    NotificationService::del($appeal->user->profile_id, $n->id);
                    $n->forceDelete();
                });

            Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:' . $appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');
            return $res;
        }

        if($action == 'approve-all') {
            AccountInterstitial::whereType('post.autospam')
                ->whereItemType('App\Status')
                ->whereNull('appeal_handled_at')
                ->whereUserId($appeal->user_id)
                ->get()
                ->each(function($report) use($meta) {
                    $report->is_spam = false;
                    $report->appeal_handled_at = now();
                    $report->save();
                    $status = Status::find($report->item_id);
                    if($status) {
                        $status->is_nsfw = $meta->is_nsfw;
                        $status->scope = 'public';
                        $status->visibility = 'public';
                        $status->save();
                        StatusService::del($status->id, true);
                    }

                    Notification::whereAction('autospam.warning')
                        ->whereProfileId($report->user->profile_id)
                        ->get()
                        ->each(function($n) use($report) {
                            NotificationService::del($report->user->profile_id, $n->id);
                            $n->forceDelete();
                        });
                });
            Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $appeal->user->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:' . $appeal->user->profile_id);
            Cache::forget('admin-dash:reports:spam-count');
            return $res;
        }

        return $res;
    }

    public function modReports(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $reports = Report::whereNull('admin_seen')
            ->orderBy('created_at','desc')
            ->paginate(6)
            ->map(function($report) {
                $r = [
                    'id' => $report->id,
                    'type' => $report->type,
                    'message' => $report->message,
                    'object_id' => $report->object_id,
                    'object_type' => $report->object_type,
                    'created_at' => $report->created_at
                ];

                if($report->profile_id) {
                    $r['reported_by_account'] = AccountService::get($report->profile_id, true);
                }

                if($report->object_type === 'App\\Status') {
                    $status = StatusService::get($report->object_id, false);
                    if(!$status) {
                        return;
                    }

                    $r['status'] = $status;

                    if($status['in_reply_to_id']) {
                        $r['parent'] = StatusService::get($status['in_reply_to_id'], false);
                    }
                }

                if($report->object_type === 'App\\Profile') {
                    $r['account'] = AccountService::get($report->object_id, false);
                }
                return $r;
            })
            ->filter()
            ->values();

        return $reports;
    }

    public function modReportHandle(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'action'    => 'required|string',
            'id' => 'required'
        ]);

        $action = $request->input('action');
        $id = $request->input('id');

        $actions = [
            'ignore',
            'cw',
            'unlist'
        ];

        if (!in_array($action, $actions)) {
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

    public function getConfiguration(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless(config('instance.enable_cc'), 400);

        return collect([
            [
                'name' => 'ActivityPub Federation',
                'description' => 'Enable activitypub federation support, compatible with Pixelfed, Mastodon and other platforms.',
                'key' => 'federation.activitypub.enabled'
            ],

            [
                'name' => 'Open Registration',
                'description' => 'Allow new account registrations.',
                'key' => 'pixelfed.open_registration'
            ],

            [
                'name' => 'Stories',
                'description' => 'Enable the ephemeral Stories feature.',
                'key' => 'instance.stories.enabled'
            ],

            [
                'name' => 'Require Email Verification',
                'description' => 'Require new accounts to verify their email address.',
                'key' => 'pixelfed.enforce_email_verification'
            ],

            [
                'name' => 'AutoSpam Detection',
                'description' => 'Detect and remove spam from public timelines.',
                'key' => 'pixelfed.bouncer.enabled'
            ],
        ])
        ->map(function($s) {
            $s['state'] = (bool) config_cache($s['key']);
            return $s;
        });
    }

    public function updateConfiguration(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless(config('instance.enable_cc'), 400);

        $this->validate($request, [
            'key' => 'required',
            'value' => 'required'
        ]);

        $allowedKeys = [
            'federation.activitypub.enabled',
            'pixelfed.open_registration',
            'instance.stories.enabled',
            'pixelfed.enforce_email_verification',
            'pixelfed.bouncer.enabled',
        ];

        $key = $request->input('key');
        $value = (bool) filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN);
        abort_if(!in_array($key, $allowedKeys), 400, 'Invalid cache key.');

        ConfigCacheService::put($key, $value);

                return collect([
            [
                'name' => 'ActivityPub Federation',
                'description' => 'Enable activitypub federation support, compatible with Pixelfed, Mastodon and other platforms.',
                'key' => 'federation.activitypub.enabled'
            ],

            [
                'name' => 'Open Registration',
                'description' => 'Allow new account registrations.',
                'key' => 'pixelfed.open_registration'
            ],

            [
                'name' => 'Stories',
                'description' => 'Enable the ephemeral Stories feature.',
                'key' => 'instance.stories.enabled'
            ],

            [
                'name' => 'Require Email Verification',
                'description' => 'Require new accounts to verify their email address.',
                'key' => 'pixelfed.enforce_email_verification'
            ],

            [
                'name' => 'AutoSpam Detection',
                'description' => 'Detect and remove spam from public timelines.',
                'key' => 'pixelfed.bouncer.enabled'
            ],
        ])
        ->map(function($s) {
            $s['state'] = (bool) config_cache($s['key']);
            return $s;
        });
    }

    public function getUsers(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);
        $this->validate($request, [
            'sort' => 'sometimes|in:asc,desc',
        ]);
        $q = $request->input('q');
        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $res = User::whereNull('status')
            ->when($q, function($query, $q) {
                return $query->where('username', 'like', '%' . $q . '%');
            })
            ->orderBy('id', $sort)
            ->cursorPaginate(10);
        return AdminUser::collection($res);
    }

    public function getUser(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $id = $request->input('user_id');
        $key = 'pf-admin-api:getUser:byId:' . $id;
        if($request->has('refresh')) {
            Cache::forget($key);
        }
        return Cache::remember($key, 86400, function() use($id) {
            $user = User::findOrFail($id);
            $profile = $user->profile;
            $account = AccountService::get($user->profile_id, true);
            $res = (new AdminUser($user))->additional(['meta' => [
                'cached_at' => str_replace('+00:00', 'Z', now()->format(DATE_RFC3339_EXTENDED)),
                'account' => $account,
                'dms_sent' => Conversation::whereFromId($profile->id)->count(),
                'report_count' => Report::where('object_id', $profile->id)->orWhere('reported_profile_id', $profile->id)->count(),
                'remote_report_count' => RemoteReport::whereAccountId($profile->id)->count(),
                'moderation' => [
                    'unlisted' => (bool) $profile->unlisted,
                    'cw' => (bool) $profile->cw,
                    'no_autolink' => (bool) $profile->no_autolink
                ]
            ]]);

            return $res;
        });
    }

    public function userAdminAction(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'id' => 'required',
            'action' => 'required|in:unlisted,cw,no_autolink,refresh_stats,verify_email,delete',
            'value' => 'sometimes'
        ]);

        $id = $request->input('id');
        $user = User::findOrFail($id);
        $profile = Profile::findOrFail($user->profile_id);
        $action = $request->input('action');

        abort_if($user->is_admin == true && $action !== 'refresh_stats', 400, 'Cannot moderate admin accounts');

        if($action === 'delete') {
            if(config('pixelfed.account_deletion') == false) {
                abort(404);
            }

            abort_if($user->is_admin, 400, 'Cannot delete an admin account.');

            $ts = now()->addMonth();

            $user->status = 'delete';
            $user->delete_after = $ts;
            $user->save();

            $profile->status = 'delete';
            $profile->delete_after = $ts;
            $profile->save();

            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.delete')
                ->accessLevel('admin')
                ->save();

            PublicTimelineService::deleteByProfileId($profile->id);
            NetworkTimelineService::deleteByProfileId($profile->id);

            if($profile->user_id) {
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
            return [
                'status' => 200,
                'msg' => 'deleted',
            ];
        } else if($action === 'refresh_stats') {
            $profile->following_count = DB::table('followers')->whereProfileId($user->profile_id)->count();
            $profile->followers_count = DB::table('followers')->whereFollowingId($user->profile_id)->count();
            $statusCount = Status::whereProfileId($user->profile_id)
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->whereIn('scope', ['public', 'unlisted', 'private'])
                ->count();
            $profile->status_count = $statusCount;
            $profile->save();
        } else if($action === 'verify_email') {
            $user->email_verified_at = now();
            $user->save();

            ModLogService::boot()
                ->objectUid($user->id)
                ->objectId($user->id)
                ->objectType('App\User::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => 'Manually verified email address',
                    'message' => 'Success!'
                ])
                ->accessLevel('admin')
                ->save();
        } else if($action === 'unlisted') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!'
                ])
                ->accessLevel('admin')
                ->save();
            $profile->unlisted = !$profile->unlisted;
            $profile->save();
        } else if($action === 'cw') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!'
                ])
                ->accessLevel('admin')
                ->save();
            $profile->cw = !$profile->cw;
            $profile->save();
        } else if($action === 'no_autolink') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!'
                ])
                ->accessLevel('admin')
                ->save();
            $profile->no_autolink = !$profile->no_autolink;
            $profile->save();
        } else {
            $profile->{$action} = filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN);
            $profile->save();

            ModLogService::boot()
                ->objectUid($user->id)
                ->objectId($user->id)
                ->objectType('App\User::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!'
                ])
                ->accessLevel('admin')
                ->save();
        }

        AccountService::del($user->profile_id);
        $account = AccountService::get($user->profile_id, true);

        return (new AdminUser($user))->additional(['meta' => [
            'account' => $account,
            'moderation' => [
                'unlisted' => (bool) $profile->unlisted,
                'cw' => (bool) $profile->cw,
                'no_autolink' => (bool) $profile->no_autolink
            ]
        ]]);
    }

    public function instances(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'q' => 'sometimes',
            'sort' => 'sometimes|in:asc,desc',
            'sort_by' => 'sometimes|in:id,status_count,user_count,domain',
            'filter' => 'sometimes|in:all,unlisted,auto_cw,banned',
        ]);

        $q = $request->input('q');
        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $sortBy = $request->input('sort_by', 'id');
        $filter = $request->input('filter');

        $res = Instance::when($q, function($query, $q) {
                return $query->where('domain', 'like', '%' . $q . '%');
            })
            ->when($filter, function($query, $filter) {
                if($filter === 'all') {
                    return $query;
                } else {
                    return $query->where($filter, true);
                }
            })
            ->when($sortBy, function($query, $sortBy) use($sort) {
                return $query->orderBy($sortBy, $sort);
            }, function($query) {
                return $query->orderBy('id', 'desc');
            })
            ->cursorPaginate(10)
            ->withQueryString();

        return AdminInstance::collection($res);
    }

    public function getInstance(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $id = $request->input('id');
        $res = Instance::findOrFail($id);

        return new AdminInstance($res);
    }

    public function moderateInstance(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'id' => 'required',
            'key' => 'required|in:unlisted,auto_cw,banned',
            'value' => 'required'
        ]);

        $id = $request->input('id');
        $key = $request->input('key');
        $value = (bool) filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN);
        $res = Instance::findOrFail($id);
        $res->{$key} = $value;
        $res->save();

        InstanceService::refresh();
        NetworkTimelineService::warmCache(true);

        return new AdminInstance($res);
    }

    public function refreshInstanceStats(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin == 1, 404);

        $this->validate($request, [
            'id' => 'required',
        ]);

        $id = $request->input('id');
        $instance = Instance::findOrFail($id);
        $instance->user_count = Profile::whereDomain($instance->domain)->count();
        $instance->status_count = Profile::whereDomain($instance->domain)->leftJoin('statuses', 'profiles.id', '=', 'statuses.profile_id')->count();
        $instance->save();

        return new AdminInstance($instance);
    }

    public function getAllStats(Request $request)
    {
        abort_if(!$request->user(), 404);
        abort_unless($request->user()->is_admin === 1, 404);

        if($request->has('refresh')) {
            Cache::forget('admin-api:instance-all-stats-v1');
        }

        return Cache::remember('admin-api:instance-all-stats-v1', 1209600, function() {
            $days = range(1, 7);
            $res = [
                'cached_at' => now()->format('c'),
            ];
            $minStatusId = SnowflakeService::byDate(now()->subDays(7));

            foreach($days as $day) {
                $label = now()->subDays($day)->format('D');
                $labelShort = substr($label, 0, 1);
                $res['users']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => User::whereDate('created_at', now()->subDays($day))->count()
                ];

                $res['posts']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Status::whereNull('uri')->where('id', '>', $minStatusId)->whereDate('created_at', now()->subDays($day))->count()
                ];

                $res['instances']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Instance::whereDate('created_at', now()->subDays($day))->count()
                ];
            }

            $res['users']['total'] = DB::table('users')->count();
            $res['users']['min'] = collect($res['users']['days'])->min('count');
            $res['users']['max'] = collect($res['users']['days'])->max('count');
            $res['users']['change'] = collect($res['users']['days'])->sum('count');;
            $res['posts']['total'] = DB::table('statuses')->whereNull('uri')->count();
            $res['posts']['min'] = collect($res['posts']['days'])->min('count');
            $res['posts']['max'] = collect($res['posts']['days'])->max('count');
            $res['posts']['change'] = collect($res['posts']['days'])->sum('count');
            $res['instances']['total'] = DB::table('instances')->count();
            $res['instances']['min'] = collect($res['instances']['days'])->min('count');
            $res['instances']['max'] = collect($res['instances']['days'])->max('count');
            $res['instances']['change'] = collect($res['instances']['days'])->sum('count');

            return $res;
        });
    }
}
