<?php

namespace App\Http\Controllers;

use App\{
	AccountInterstitial,
	Contact,
	Hashtag,
	Newsroom,
	OauthClient,
	Profile,
	Report,
	Status,
	User
};
use DB, Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\{
	AdminDiscoverController,
	AdminInstanceController,
	AdminReportController,
	AdminMediaController,
	AdminSettingsController,
	AdminSupportController,
	AdminUserController
};
use Illuminate\Validation\Rule;
use App\Services\AdminStatsService;

class AdminController extends Controller
{
	use AdminReportController, 
	AdminDiscoverController, 
	AdminMediaController, 
	AdminSettingsController, 
	AdminInstanceController,
	AdminUserController;

	public function __construct()
	{
		$this->middleware('admin');
		$this->middleware('dangerzone');
		$this->middleware('twofactor');
	}

	public function home()
	{
		$data = AdminStatsService::get();
		return view('admin.home', compact('data'));
	}

	public function statuses(Request $request)
	{
		$statuses = Status::orderBy('id', 'desc')->simplePaginate(10);

		return view('admin.statuses.home', compact('statuses'));
	}

	public function showStatus(Request $request, $id)
	{
		$status = Status::findOrFail($id);

		return view('admin.statuses.show', compact('status'));
	}

	public function reports(Request $request)
	{
		$this->validate($request, [
			'filter' => 'nullable|string|in:all,open,closed'
		]);
		$filter = $request->input('filter');
		$reports = Report::orderBy('created_at','desc')
		->when($filter, function($q, $filter) {
			return $filter == 'open' ? 
			$q->whereNull('admin_seen') :
			$q->whereNotNull('admin_seen');
		})
		->paginate(4);
		return view('admin.reports.home', compact('reports'));
	}

	public function showReport(Request $request, $id)
	{
		$report = Report::findOrFail($id);
		return view('admin.reports.show', compact('report'));
	}

	public function appeals(Request $request)
	{
		$appeals = AccountInterstitial::whereNotNull('appeal_requested_at')
			->whereNull('appeal_handled_at')
			->latest()
			->paginate(6);
		return view('admin.reports.appeals', compact('appeals'));
	}

	public function showAppeal(Request $request, $id)
	{
		$appeal = AccountInterstitial::whereNotNull('appeal_requested_at')
			->whereNull('appeal_handled_at')
			->findOrFail($id);
		$meta = json_decode($appeal->meta);
		return view('admin.reports.show_appeal', compact('appeal', 'meta'));
	}

	public function spam(Request $request)
	{
		$appeals = AccountInterstitial::whereType('post.autospam')
			->whereNull('appeal_handled_at')
			->latest()
			->paginate(6);
		return view('admin.reports.spam', compact('appeals'));
	}

	public function showSpam(Request $request, $id)
	{
		$appeal = AccountInterstitial::whereType('post.autospam')
			->whereNull('appeal_handled_at')
			->findOrFail($id);
		$meta = json_decode($appeal->meta);
		return view('admin.reports.show_spam', compact('appeal', 'meta'));
	}

	public function updateSpam(Request $request, $id)
	{
		$this->validate($request, [
			'action' => 'required|in:dismiss,approve'
		]);

		$action = $request->input('action');
		$appeal = AccountInterstitial::whereType('post.autospam')
			->whereNull('appeal_handled_at')
			->findOrFail($id);

		$meta = json_decode($appeal->meta);

		if($action == 'dismiss') {
			$appeal->appeal_handled_at = now();
			$appeal->save();

			return redirect('/i/admin/reports/autospam');
		}

		$status = $appeal->status;
		$status->is_nsfw = $meta->is_nsfw;
		$status->scope = 'public';
		$status->visibility = 'public';
		$status->save();
			
		$appeal->appeal_handled_at = now();
		$appeal->save();

		return redirect('/i/admin/reports/autospam');
	}

	public function updateAppeal(Request $request, $id)
	{
		$this->validate($request, [
			'action' => 'required|in:dismiss,approve'
		]);

		$action = $request->input('action');
		$appeal = AccountInterstitial::whereNotNull('appeal_requested_at')
			->whereNull('appeal_handled_at')
			->findOrFail($id);

		if($action == 'dismiss') {
			$appeal->appeal_handled_at = now();
			$appeal->save();

			return redirect('/i/admin/reports/appeals');
		}

		switch ($appeal->type) {
			case 'post.cw':
				$status = $appeal->status;
				$status->is_nsfw = false;
				$status->save();
				break;

			case 'post.unlist':
				$status = $appeal->status;
				$status->scope = 'public';
				$status->visibility = 'public';
				$status->save();
				break;
			
			default:
				# code...
				break;
		}

		$appeal->appeal_handled_at = now();
		$appeal->save();

		return redirect('/i/admin/reports/appeals');
	}

	public function profiles(Request $request)
	{
		$this->validate($request, [
			'search' => 'nullable|string|max:250',
			'filter' => [
				'nullable',
				'string',
				Rule::in(['all', 'local', 'remote'])
			]
		]);
		$search = $request->input('search');
		$filter = $request->input('filter');
		$limit = 12;
		$profiles = Profile::select('id','username')
			->whereNull('status')
			->when($search, function($q, $search) {
				return $q->where('username', 'like', "%$search%");
			})->when($filter, function($q, $filter) {
				if($filter == 'local') {
					return $q->whereNull('domain');
				}
				if($filter == 'remote') {
					return $q->whereNotNull('domain');
				}
				return $q;
			})->orderByDesc('id')
			->simplePaginate($limit);

		return view('admin.profiles.home', compact('profiles'));
	}

	public function profileShow(Request $request, $id)
	{
		$profile = Profile::findOrFail($id);
		$user = $profile->user;
		return view('admin.profiles.edit', compact('profile', 'user'));
	}

	public function appsHome(Request $request)
	{
		$filter = $request->input('filter');
		if(in_array($filter, ['revoked'])) {
			$apps = OauthClient::with('user')
			->whereNotNull('user_id')
			->whereRevoked(true)
			->orderByDesc('id')
			->paginate(10);
		} else {
			$apps = OauthClient::with('user')
			->whereNotNull('user_id')
			->orderByDesc('id')
			->paginate(10);
		}
		return view('admin.apps.home', compact('apps'));
	}

	public function hashtagsHome(Request $request)
	{
		$hashtags = Hashtag::orderByDesc('id')->paginate(10);
		return view('admin.hashtags.home', compact('hashtags'));
	}

	public function messagesHome(Request $request)
	{
		$messages = Contact::orderByDesc('id')->paginate(10);
		return view('admin.messages.home', compact('messages'));
	}

	public function messagesShow(Request $request, $id)
	{
		$message = Contact::findOrFail($id);
		return view('admin.messages.show', compact('message'));
	}

	public function messagesMarkRead(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|integer|min:1'
		]);
		$id = $request->input('id');
		$message = Contact::findOrFail($id);
		if($message->read_at) {
			return;
		}
		$message->read_at = now();
		$message->save();
		return;
	}

	public function newsroomHome(Request $request)
	{
		$newsroom = Newsroom::latest()->paginate(10);
		return view('admin.newsroom.home', compact('newsroom'));
	}

	public function newsroomCreate(Request $request)
	{
		return view('admin.newsroom.create');
	}

	public function newsroomEdit(Request $request, $id)
	{
		$news = Newsroom::findOrFail($id);
		return view('admin.newsroom.edit', compact('news'));
	}

	public function newsroomDelete(Request $request, $id)
	{
		$news = Newsroom::findOrFail($id);
		$news->delete();
		return redirect('/i/admin/newsroom');
	}

	public function newsroomUpdate(Request $request, $id)
	{
		$this->validate($request, [
			'title' => 'required|string|min:1|max:100',
			'summary' => 'nullable|string|max:200',
			'body'  => 'nullable|string'
		]);
		$changed = false;
		$changedFields = [];
		$news = Newsroom::findOrFail($id);
		$fields = [
			'title' => 'string',
			'summary' => 'string',
			'body' => 'string',
			'category' => 'string',
			'show_timeline' => 'boolean',
			'auth_only' => 'boolean',
			'show_link' => 'boolean',
			'force_modal' => 'boolean',
			'published' => 'published'
		];
		foreach($fields as $field => $type) {
			switch ($type) {
				case 'string':
				if($request->{$field} != $news->{$field}) {
					if($field == 'title') {
						$news->slug = str_slug($request->{$field});
					}
					$news->{$field} = $request->{$field};
					$changed = true;
					array_push($changedFields, $field);
				}
				break;

				case 'boolean':
				$state = $request->{$field} == 'on' ? true : false;
				if($state != $news->{$field}) {
					$news->{$field} = $state;
					$changed = true;
					array_push($changedFields, $field);
				}
				break;
				case 'published':
				$state = $request->{$field} == 'on' ? true : false;
				$published = $news->published_at != null;
				if($state != $published) {
					$news->published_at = $state ? now() : null;
					$changed = true;
					array_push($changedFields, $field);
				}
				break;

			}
		}

		if($changed) {
			$news->save();
		}
		$redirect = $news->published_at ? $news->permalink() : $news->editUrl();
		return redirect($redirect);
	}


	public function newsroomStore(Request $request)
	{
		$this->validate($request, [
			'title' => 'required|string|min:1|max:100',
			'summary' => 'nullable|string|max:200',
			'body'  => 'nullable|string'
		]);
		$changed = false;
		$changedFields = [];
		$news = new Newsroom();
		$fields = [
			'title' => 'string',
			'summary' => 'string',
			'body' => 'string',
			'category' => 'string',
			'show_timeline' => 'boolean',
			'auth_only' => 'boolean',
			'show_link' => 'boolean',
			'force_modal' => 'boolean',
			'published' => 'published'
		];
		foreach($fields as $field => $type) {
			switch ($type) {
				case 'string':
				if($request->{$field} != $news->{$field}) {
					if($field == 'title') {
						$news->slug = str_slug($request->{$field});
					}
					$news->{$field} = $request->{$field};
					$changed = true;
					array_push($changedFields, $field);
				}
				break;

				case 'boolean':
				$state = $request->{$field} == 'on' ? true : false;
				if($state != $news->{$field}) {
					$news->{$field} = $state;
					$changed = true;
					array_push($changedFields, $field);
				}
				break;
				case 'published':
				$state = $request->{$field} == 'on' ? true : false;
				$published = $news->published_at != null;
				if($state != $published) {
					$news->published_at = $state ? now() : null;
					$changed = true;
					array_push($changedFields, $field);
				}
				break;

			}
		}

		if($changed) {
			$news->save();
		}
		$redirect = $news->published_at ? $news->permalink() : $news->editUrl();
		return redirect($redirect);
	}
}
