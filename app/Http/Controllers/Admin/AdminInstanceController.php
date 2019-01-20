<?php

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{Instance, Profile};
use Carbon\Carbon;
use Illuminate\Http\Request;

trait AdminInstanceController
{

	public function instances(Request $request)
	{
		$instances = Instance::orderByDesc('id')->paginate(5);
		return view('admin.instances.home', compact('instances'));
	}

	public function instanceScan(Request $request)
	{
		DB::transaction(function() {
			Profile::whereNotNull('domain')
				->groupBy('domain')
				->chunk(50, function($domains) {
					foreach($domains as $domain) {
						Instance::firstOrCreate([
							'domain' => $domain->domain
						]);
					}
				});
		});
		return redirect()->back();
	}

}