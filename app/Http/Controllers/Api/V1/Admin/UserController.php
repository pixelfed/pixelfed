<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Models\Conversation;
use App\Models\RemoteReport;
use App\Profile;
use App\Report;
use App\Services\AccountService;
use App\Services\ModLogService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Status;
use App\User;
use Cache;
use DB;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $this->validate($request, [
            'sort' => 'sometimes|in:asc,desc',
        ]);
        $q = $request->input('q');
        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $res = User::whereNull('status')
            ->when($q, function ($query, $q) {
                return $query->where('username', 'like', '%'.$q.'%');
            })
            ->orderBy('id', $sort)
            ->cursorPaginate(10);

        return AdminUser::collection($res);
    }

    public function getUser(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $id = $request->input('user_id');
        $key = 'pf-admin-api:getUser:byId:'.$id;
        if ($request->has('refresh')) {
            Cache::forget($key);
        }

        return Cache::remember($key, 86400, function () use ($id) {
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
                    'no_autolink' => (bool) $profile->no_autolink,
                ],
            ]]);

            return $res;
        });
    }    
public function userAdminAction(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

        $this->validate($request, [
            'id' => 'required',
            'action' => 'required|in:unlisted,cw,no_autolink,refresh_stats,verify_email,delete',
            'value' => 'sometimes',
        ]);

        $id = $request->input('id');
        $user = User::findOrFail($id);
        $profile = Profile::findOrFail($user->profile_id);
        $action = $request->input('action');

        abort_if($user->is_admin == true && $action !== 'refresh_stats', 400, 'Cannot moderate admin accounts');

        if ($action === 'delete') {
            if (config('pixelfed.account_deletion') == false) {
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

            return [
                'status' => 200,
                'msg' => 'deleted',
            ];
        } elseif ($action === 'refresh_stats') {
            $profile->following_count = DB::table('followers')->whereProfileId($user->profile_id)->count();
            $profile->followers_count = DB::table('followers')->whereFollowingId($user->profile_id)->count();
            $statusCount = Status::whereProfileId($user->profile_id)
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->whereIn('scope', ['public', 'unlisted', 'private'])
                ->count();
            $profile->status_count = $statusCount;
            $profile->save();
        } elseif ($action === 'verify_email') {
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
                    'message' => 'Success!',
                ])
                ->accessLevel('admin')
                ->save();
        } elseif ($action === 'unlisted') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!',
                ])
                ->accessLevel('admin')
                ->save();
            $profile->unlisted = ! $profile->unlisted;
            $profile->save();
        } elseif ($action === 'cw') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!',
                ])
                ->accessLevel('admin')
                ->save();
            $profile->cw = ! $profile->cw;
            $profile->save();
        } elseif ($action === 'no_autolink') {
            ModLogService::boot()
                ->objectUid($profile->id)
                ->objectId($profile->id)
                ->objectType('App\Profile::class')
                ->user($request->user())
                ->action('admin.user.moderate')
                ->metadata([
                    'action' => $action,
                    'message' => 'Success!',
                ])
                ->accessLevel('admin')
                ->save();
            $profile->no_autolink = ! $profile->no_autolink;
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
                    'message' => 'Success!',
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
                'no_autolink' => (bool) $profile->no_autolink,
            ],
        ]]);
    }
}