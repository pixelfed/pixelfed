<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminInstance;
use App\Instance;
use App\Profile;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use App\Services\SnowflakeService;
use App\Status;
use App\User;
use Cache;
use DB;
use Illuminate\Http\Request;

class InstanceController extends Controller
{
    public function instances(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

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

        $res = Instance::when($q, function ($query, $q) {
            return $query->where('domain', 'like', '%'.$q.'%');
        })
            ->when($filter, function ($query, $filter) {
                if ($filter === 'all') {
                    return $query;
                } else {
                    return $query->where($filter, true);
                }
            })
            ->when($sortBy, function ($query, $sortBy) use ($sort) {
                return $query->orderBy($sortBy, $sort);
            }, function ($query) {
                return $query->orderBy('id', 'desc');
            })
            ->cursorPaginate(10)
            ->withQueryString();

        return AdminInstance::collection($res);
    }

    public function getInstance(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $id = $request->input('id');
        $res = Instance::findOrFail($id);

        return new AdminInstance($res);
    }

    public function moderateInstance(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

        $this->validate($request, [
            'id' => 'required',
            'key' => 'required|in:unlisted,auto_cw,banned',
            'value' => 'required',
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
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

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
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin === 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        if ($request->has('refresh')) {
            Cache::forget('admin-api:instance-all-stats-v1');
        }

        return Cache::remember('admin-api:instance-all-stats-v1', 1209600, function () {
            $days = range(1, 7);
            $res = [
                'cached_at' => now()->format('c'),
            ];
            $minStatusId = SnowflakeService::byDate(now()->subDays(7));

            foreach ($days as $day) {
                $label = now()->subDays($day)->format('D');
                $labelShort = substr($label, 0, 1);
                $res['users']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => User::whereDate('created_at', now()->subDays($day))->count(),
                ];

                $res['posts']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Status::whereNull('uri')->where('id', '>', $minStatusId)->whereDate('created_at', now()->subDays($day))->count(),
                ];

                $res['instances']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Instance::whereDate('created_at', now()->subDays($day))->count(),
                ];
            }

            $res['users']['total'] = DB::table('users')->count();
            $res['users']['min'] = collect($res['users']['days'])->min('count');
            $res['users']['max'] = collect($res['users']['days'])->max('count');
            $res['users']['change'] = collect($res['users']['days'])->sum('count');
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