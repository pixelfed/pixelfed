<?php

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{Instance, Profile};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\InstanceService;
use App\Http\Resources\AdminInstance;

trait AdminInstanceController
{
	public function instances(Request $request)
	{
		return view('admin.instances.home');
	}

	public function instanceScan(Request $request)
	{
		Profile::whereNotNull('domain')
			->latest()
			->groupBy(['domain', 'id'])
			->where('created_at', '>', now()->subMonths(2))
			->chunk(100, function($domains) {
				foreach($domains as $domain) {
					Instance::firstOrCreate([
						'domain' => $domain->domain
					]);
				}
		});

		return redirect()->back();
	}

	public function instanceShow(Request $request, $id)
	{
		$instance = Instance::findOrFail($id);
		return view('admin.instances.show', compact('instance'));
	}

	public function instanceEdit(Request $request, $id)
	{
		$this->validate($request, [
			'action' => [
				'required',
				'string',
				'min:1',
				'max:20',
				Rule::in(['autocw', 'unlist', 'ban'])
			],
		]);

		$instance = Instance::findOrFail($id);
		$unlisted = $instance->unlisted;
		$autocw = $instance->auto_cw;
		$banned = $instance->banned;

		switch ($request->action) {
			case 'autocw':
				$instance->auto_cw = $autocw == true ? false : true;
				$instance->save();
				break;

			case 'unlist':
				$instance->unlisted = $unlisted == true ? false : true;
				$instance->save();
				break;

			case 'ban':
				$instance->banned = $banned == true ? false : true;
				$instance->save();
				break;
		}

		Cache::forget(InstanceService::CACHE_KEY_BANNED_DOMAINS);
		Cache::forget(InstanceService::CACHE_KEY_UNLISTED_DOMAINS);
		Cache::forget(InstanceService::CACHE_KEY_NSFW_DOMAINS);

		return response()->json([]);
	}

	public function getInstancesStatsApi(Request $request)
	{
		return InstanceService::stats();
	}

	public function getInstancesQueryApi(Request $request)
	{
		$this->validate($request, [
			'q' => 'required'
		]);

		$q = $request->input('q');

		return AdminInstance::collection(
			Instance::where('domain', 'like', '%' . $q . '%')
			->orderByDesc('user_count')
			->cursorPaginate(20)
			->withQueryString()
		);
	}

	public function getInstancesApi(Request $request)
	{
		$this->validate($request, [
			'filter' => [
				'nullable',
				'string',
				'min:1',
				'max:20',
				Rule::in([
					'cw',
					'unlisted',
					'banned',
					'popular_users',
					'popular_statuses',
					'new',
					'all'
				])
			],
		]);
		$filter = $request->input('filter');
		$query = $request->input('q');

		return AdminInstance::collection(Instance::when($query, function($q, $qq) use($query) {
				return $q->where('domain', 'like', '%' . $query . '%');
			})
			->when($filter, function($q, $f) use($filter) {
				if($filter == 'cw') { return $q->whereAutoCw(true)->orderByDesc('id'); }
				if($filter == 'unlisted') { return $q->whereUnlisted(true)->orderByDesc('id'); }
				if($filter == 'banned') { return $q->whereBanned(true)->orderByDesc('id'); }
				if($filter == 'new') { return $q->orderByDesc('id'); }
				if($filter == 'popular_users') { return $q->orderByDesc('user_count'); }
				if($filter == 'popular_statuses') { return $q->orderByDesc('status_count'); }
				return $q->orderByDesc('id');
			}, function($q) {
				return $q->orderByDesc('id');
			})
			->cursorPaginate(10)
			->withQueryString());
	}

	public function postInstanceUpdateApi(Request $request)
	{
		$this->validate($request, [
			'id' => 'required',
			'banned' => 'boolean',
			'auto_cw' => 'boolean',
			'unlisted' => 'boolean',
			'notes' => 'nullable|string|max:500',
		]);

		$id = $request->input('id');
		$instance = Instance::findOrFail($id);
		$instance->update($request->only([
			'banned',
			'auto_cw',
			'unlisted',
			'notes'
		]));

		InstanceService::refresh();

		return new AdminInstance($instance);
	}

	public function postInstanceCreateNewApi(Request $request)
	{
		$this->validate($request, [
			'domain' => 'required|string',
			'banned' => 'boolean',
			'auto_cw' => 'boolean',
			'unlisted' => 'boolean',
			'notes' => 'nullable|string|max:500'
		]);

		$domain = $request->input('domain');

		abort_if(!strpos($domain, '.'), 400, 'Invalid domain');
		abort_if(!filter_var($domain, FILTER_VALIDATE_DOMAIN), 400, 'Invalid domain');

		$instance = new Instance;
		$instance->domain = $request->input('domain');
		$instance->banned = $request->input('banned');
		$instance->auto_cw = $request->input('auto_cw');
		$instance->unlisted = $request->input('unlisted');
		$instance->manually_added = true;
		$instance->notes = $request->input('notes');
		$instance->save();

		InstanceService::refresh();

		return new AdminInstance($instance);
	}

	public function postInstanceRefreshStatsApi(Request $request)
	{
		$this->validate($request, [
			'id' => 'required'
		]);

		$instance = Instance::findOrFail($request->input('id'));
		$instance->user_count = Profile::whereDomain($instance->domain)->count();
        $instance->status_count = Profile::whereDomain($instance->domain)->leftJoin('statuses', 'profiles.id', '=', 'statuses.profile_id')->count();
        $instance->save();

        return new AdminInstance($instance);
	}

	public function postInstanceDeleteApi(Request $request)
	{
		$this->validate($request, [
			'id' => 'required'
		]);

		$instance = Instance::findOrFail($request->input('id'));
		$instance->delete();

		InstanceService::refresh();

		return 200;
	}
}
