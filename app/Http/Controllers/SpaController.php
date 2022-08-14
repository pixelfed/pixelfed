<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;
use DB;
use League\CommonMark\CommonMarkConverter;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Services\SnowflakeService;
use App\Util\Localization\Localization;

class SpaController extends Controller
{
    public function index(Request $req)
    {
    	abort_unless(config('exp.spa'), 404);
    	if(!$req->user()) { return redirect('/login'); }
    	return view('layouts.spa');
    }

	public function webPost(Request $request, $id)
	{
    	abort_unless(config('exp.spa'), 404);
		if($request->user()) {
			return view('layouts.spa');
		}

		$post = StatusService::get($id, false);

		if($post && !in_array($post['visibility'], ['public', 'unlisted'])) {
			return redirect('/login');
		}

		if(
			$post &&
			isset($post['url']) &&
			isset($post['local']) &&
			$post['local'] === true
		) {
			return redirect($post['url']);
		}

    	return redirect('/login');
	}

	public function webProfile(Request $request, $id)
	{
    	abort_unless(config('exp.spa'), 404);
		if($request->user()) {
			if(substr($id, 0, 1) == '@') {
				$id = AccountService::usernameToId(substr($id, 1));
				return redirect("/i/web/profile/{$id}");
			}
			return view('layouts.spa');
		}

		$account = AccountService::get($id);

		if($account && isset($account['url']) && $account['local']) {
			return redirect($account['url']);
		}

		return redirect('/login');
	}

	public function updateLanguage(Request $request)
	{
		abort_unless(config('exp.spa'), 404);
		abort_unless($request->user(), 404);
		$this->validate($request, [
			'v' => 'required|in:0.1,0.2',
			'l' => 'required|alpha_dash|max:5'
		]);

		$lang = $request->input('l');
		$user = $request->user();

		abort_if(!in_array($lang, Localization::languages()), 400);

		$user->language = $lang;
		$user->save();
		session()->put('locale', $lang);

		return ['language' => $lang];
	}

	public function getPrivacy(Request $request)
	{
		abort_unless($request->user(), 404);
		$body = $this->markdownToHtml('views/page/privacy.md');
		return [
			'body' => $body
		];
	}

	public function getTerms(Request $request)
	{
		abort_unless($request->user(), 404);
		$body = $this->markdownToHtml('views/page/terms.md');
		return [
			'body' => $body
		];
	}

	protected function markdownToHtml($src, $ttl = 600)
	{
		return Cache::remember(
			'pf:doc_cache:markdown:' . $src,
			$ttl,
			function() use($src) {
				$path = resource_path($src);
				$file = file_get_contents($path);
				$converter = new CommonMarkConverter();
				return (string) $converter->convertToHtml($file);
		});
	}

	public function usernameRedirect(Request $request, $username)
	{
		abort_unless($request->user(), 404);
		$id = AccountService::usernameToId($username);
		if(!$id) {
			return redirect('/i/web/404');
		}
		return redirect('/i/web/profile/' . $id);
	}

	public function hashtagRedirect(Request $request, $tag)
	{
		if(!$request->user()) {
			return redirect('/discover/tags/' . $tag);
		}

		return view('layouts.spa');
	}
}
