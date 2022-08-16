<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth, Cache;
use App\Page;
use App\Services\ConfigCacheService;

class PageController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth', 'admin']);
	}

	protected function cacheKeys() {
		return [
			'/site/about' => 'site:about',
			'/site/privacy' => 'site:privacy',
			'/site/terms' => 'site:terms',
			'/site/kb/community-guidelines' => 'site:help:community-guidelines',
			'/site/legal-notice' => 'site:legal-notice'
		];
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
		if(in_array($slug, array_keys($this->cacheKeys())) == false) {
			return redirect(route('admin.settings.pages'));
		}
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
		$keys = $this->cacheKeys();
		$key = $keys[$page->slug];
		Cache::forget($key);
		if($page->slug === '/site/legal-notice') {
			ConfigCacheService::put('instance.has_legal_notice', $page->active);
		}
		return response()->json(['msg' => 200]);
	}

	public function delete(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|integer|min:1|exists:pages,id'
		]);

		$page = Page::findOrFail($request->input('id'));
		$keys = $this->cacheKeys();
		$key = $keys[$page->slug];
		$page->delete();
		Cache::forget($key);
		return redirect(route('admin.settings.pages'));
	}

	public function generatePage(Request $request)
	{
		$this->validate($request, [
			'page' => 'required|string|in:about,terms,privacy,community_guidelines,legal_notice',
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

			case 'community_guidelines':
				Page::firstOrCreate(['slug' => '/site/kb/community-guidelines']);
				break;

			case 'legal_notice':
				Page::firstOrCreate(['slug' => '/site/legal-notice']);
				break;
		}

		return redirect(route('admin.settings.pages'));
	}
}
