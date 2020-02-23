<?php

namespace App\Http\Controllers\Admin;

use DB;
use Cache;
use App\Instance;
use App\Profile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait AdminInstanceController
{

	public function instances(Request $request)
	{
		$this->validate($request, [
			'filter' => [
				'nullable',
				'string',
				'min:1',
				'max:20',
				Rule::in(['autocw', 'unlisted', 'banned'])
			],
		]);
		if($request->has('filter') && $request->filled('filter')) {
			switch ($request->filter) {
				case 'autocw':
					$instances = Instance::whereAutoCw(true)->orderByDesc('id')->paginate(5);
					break;
				case 'unlisted':
					$instances = Instance::whereUnlisted(true)->orderByDesc('id')->paginate(5);
					break;
				case 'banned':
					$instances = Instance::whereBanned(true)->orderByDesc('id')->paginate(5);
					break;
			}
		} else {
			$instances = Instance::orderByDesc('id')->paginate(5);
		}
		return view('admin.instances.home', compact('instances'));
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

		return response()->json([]);
	}
}