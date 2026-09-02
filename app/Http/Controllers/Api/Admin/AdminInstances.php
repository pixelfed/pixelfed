<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Resources\AdminInstance;
use App\Models\Instance;
use App\Models\Profile;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use Illuminate\Http\Request;

trait AdminInstances
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
}
