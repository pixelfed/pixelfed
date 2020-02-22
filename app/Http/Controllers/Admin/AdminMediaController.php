<?php

namespace App\Http\Controllers\Admin;

use DB, Cache;
use App\{
	Media,
	Profile,
	Status
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait AdminMediaController
{
	public function media(Request $request)
	{
		$this->validate($request, [
			'layout' => [
				'nullable',
				'string',
				'min:1',
				'max:4',
				Rule::in(['grid','list'])
			],
			'search' => 'nullable|string|min:1|max:20'
		]);
		if($request->filled('search')) {
			$profiles = Profile::where('username', 'like', '%'.$request->input('search').'%')->pluck('id')->toArray();
			$media = Media::whereHas('status')
				->with('status')
				->orderby('id', 'desc')
				->whereIn('profile_id', $profiles)
				->orWhere('mime', $request->input('search'))
				->paginate(12);
		} else {
			$media = Media::whereHas('status')->with('status')->orderby('id', 'desc')->paginate(12);
		}
		return view('admin.media.home', compact('media'));
	}

	public function mediaShow(Request $request, $id)
	{
		$media = Media::findOrFail($id);
		return view('admin.media.show', compact('media'));
	}
}