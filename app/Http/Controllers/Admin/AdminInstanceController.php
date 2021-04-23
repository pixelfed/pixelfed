<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{Instance, Profile};
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
				Rule::in([
					'cw',
					'unlisted',
					'banned',
					// 'popular',
					'new',
					'all'
				])
			],
		]);
		if($request->has('q') && $request->filled('q')) {
			$instances = Instance::where('domain', 'like', '%' . $request->input('q') . '%')->simplePaginate(10);
		} else if($request->has('filter') && $request->filled('filter')) {
			switch ($request->filter) {
				case 'cw':
					$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->whereAutoCw(true)->orderByDesc('id')->simplePaginate(10);
					break;
				case 'unlisted':
					$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->whereUnlisted(true)->orderByDesc('id')->simplePaginate(10);
					break;
				case 'banned':
					$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->whereBanned(true)->orderByDesc('id')->simplePaginate(10);
					break;
				case 'new':
					$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->latest()->simplePaginate(10);
					break;
				// case 'popular':
				// 	$popular = Profile::selectRaw('*, count(domain) as count')
				// 		->whereNotNull('domain')
				// 		->groupBy('domain')
				// 		->orderByDesc('count')
				// 		->take(10)
				// 		->get()
				// 		->pluck('domain')
				// 		->toArray();
				// 	$instances = Instance::whereIn('domain', $popular)->simplePaginate(10);
				// 	break;

				default:
					$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->orderByDesc('id')->simplePaginate(10);
				break;
			}
		} else {
			$instances = Instance::select('id', 'domain', 'unlisted', 'auto_cw', 'banned')->orderByDesc('id')->simplePaginate(10);
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

		Cache::forget('instances:banned:domains');
		Cache::forget('instances:unlisted:domains');
		Cache::forget('instances:auto_cw:domains');

		return response()->json([]);
	}
}
