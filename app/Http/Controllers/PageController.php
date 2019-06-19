<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Page;

class PageController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth', 'admin']);
	}

	protected function authCheck($admin_only = false)
	{
		$auth = $admin_only ?
			Auth::check() && Auth::user()->is_admin == true :
			Auth::check();
		if($auth == false) {
			abort(403);
		}
	}

	public function edit(Request $request)
	{
		$this->authCheck(true);
		$this->validate($request, [
			'page'	=> 'required|string'
		]);
		$slug = urldecode($request->page);
		$page = Page::firstOrCreate(['slug' => $slug]);
		return view('admin.pages.edit', compact('page'));
	}

	public function store(Request $request)
	{
		$this->validate($request, [
			'slug' => 'required|string',
			'content' => 'required|string',
			'title' => 'nullable|string',
			'active'  => 'required|boolean'
		]);
		$slug = urldecode($request->input('slug'));
		$page = Page::firstOrCreate(['slug' => $slug]);
		$page->content = $request->input('content');
		$page->title = $request->input('title');
		$page->active = (bool) $request->input('active');
		$page->save();
		return response()->json(['msg' => 200]);
	}

	public function generatePage(Request $request)
	{
		$this->validate($request, [
			'page' => 'required|string|in:about,terms,privacy',
		]);

		$page = $request->input('page');

		switch ($page) {
			case 'about':
				Page::firstOrCreate(['slug' => '/site/about']);
				break;

			case 'privacy':
				Page::firstOrCreate(['slug' => '/site/privacy']);
				break;

			case 'terms':
				Page::firstOrCreate(['slug' => '/site/terms']);
				break;
		}

		return redirect(route('admin.settings.pages'));
	}
}
