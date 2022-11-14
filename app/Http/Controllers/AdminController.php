<?php

namespace App\Http\Controllers;

use App\{
	AccountInterstitial,
	Contact,
	Hashtag,
	Instance,
	Newsroom,
	OauthClient,
	Profile,
	Report,
	Status,
	Story,
	User
};
use DB, Cache, Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Admin\{
	AdminDirectoryController,
	AdminDiscoverController,
	AdminInstanceController,
	AdminReportController,
	// AdminGroupsController,
	AdminMediaController,
	AdminSettingsController,
	// AdminStorageController,
	AdminSupportController,
	AdminUserController
};
use Illuminate\Validation\Rule;
use App\Services\AdminStatsService;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Services\StoryService;
use App\Models\CustomEmoji;

class AdminController extends Controller
{
	use AdminReportController, 
	AdminDirectoryController,
	AdminDiscoverController,
	// AdminGroupsController,
	AdminMediaController, 
	AdminSettingsController, 
	AdminInstanceController,
	// AdminStorageController,
	AdminUserController;

	public function __construct()
	{
		$this->middleware('admin');
		$this->middleware('dangerzone');
		$this->middleware('twofactor');
	}

	public function home()
	{
		return view('admin.home');
	}

	public function stats()
	{
		$data = AdminStatsService::get();
		return view('admin.stats', compact('data'));
	}

	public function getStats()
	{
		return AdminStatsService::summary();
	}

	public function getAccounts()
	{
		$users = User::orderByDesc('id')->cursorPaginate(10);

		$res = [
			"next_page_url" => $users->nextPageUrl(),
			"data" => $users->map(function($user) {
				$account = AccountService::get($user->profile_id, true);
				if(!$account) {
					return [
						"id" => $user->profile_id,
						"username" => $user->username,
						"status" => "deleted",
						"avatar" => "/storage/avatars/default.jpg",
						"created_at" => $user->created_at
					];
				}
				$account['user_id'] = $user->id;
				return $account;
			})
			->filter(function($user) {
				return $user;
			})
		];
		return $res;
	}

	public function getPosts()
	{
		$posts = DB::table('statuses')
			->orderByDesc('id')
			->cursorPaginate(10);

		$res = [
			"next_page_url" => $posts->nextPageUrl(),
			"data" => $posts->map(function($post) {
				$status = StatusService::get($post->id, false);
				if(!$status) {
					return ["id" => $post->id, "created_at" => $post->created_at];
				}
				return $status;
			})
		];

		return $res;
	}

	public function getInstances()
	{
		return Instance::orderByDesc('id')->cursorPaginate(10);
	}

	public function statuses(Request $request)
	{
		$statuses = Status::orderBy('id', 'desc')->cursorPaginate(10);
		$data = $statuses->map(function($status) {
			return StatusService::get($status->id, false);
		})
		->filter(function($s) {
			return $s;
		})
		->toArray();
		return view('admin.statuses.home', compact('statuses', 'data'));
	}

	public function showStatus(Request $request, $id)
	{
		$status = Status::findOrFail($id);

		return view('admin.statuses.show', compact('status'));
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
		if($filter == 'revoked') {
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

	public function diagnosticsHome(Request $request)
	{
		return view('admin.diagnostics.home');
	}

	public function diagnosticsDecrypt(Request $request)
	{
		$this->validate($request, [
			'payload' => 'required'
		]);

		$key = 'exception_report:';
		$decrypted = decrypt($request->input('payload'));

		if(!starts_with($decrypted, $key)) {
			abort(403, 'Can only decrypt error diagnostics');
		}

		$res = [
			'decrypted' => substr($decrypted, strlen($key))
		];

		return response()->json($res);
	}

	public function stories(Request $request)
	{
		$stories = Story::with('profile')->latest()->paginate(10);
		$stats = StoryService::adminStats();
		return view('admin.stories.home', compact('stories', 'stats'));
	}

	public function customEmojiHome(Request $request)
	{
		if(!config('federation.custom_emoji.enabled')) {
			return view('admin.custom-emoji.not-enabled');
		}
		$this->validate($request, [
			'sort' => 'sometimes|in:all,local,remote,duplicates,disabled,search'
		]);

		if($request->has('cc')) {
			Cache::forget('pf:admin:custom_emoji:stats');
			Cache::forget('pf:custom_emoji');
			return redirect(route('admin.custom-emoji'));
		}

		$sort = $request->input('sort') ?? 'all';

		if($sort == 'search' && empty($request->input('q'))) {
			return redirect(route('admin.custom-emoji'));
		}

		$pg = config('database.default') == 'pgsql';

		$emojis = CustomEmoji::when($sort, function($query, $sort) use($request, $pg) {
			if($sort == 'all') {
				if($pg) {
					return $query->latest();
				} else {
					return $query->groupBy('shortcode')->latest();
				}
			} else if($sort == 'local') {
				return $query->latest()->where('domain', '=', config('pixelfed.domain.app'));
			} else if($sort == 'remote') {
				return $query->latest()->where('domain', '!=', config('pixelfed.domain.app'));
			} else if($sort == 'duplicates') {
				return $query->latest()->groupBy('shortcode')->havingRaw('count(*) > 1');
			} else if($sort == 'disabled') {
				return $query->latest()->whereDisabled(true);
			} else if($sort == 'search') {
				$q = $query
					->latest()
					->where('shortcode', 'like', '%' . $request->input('q') . '%')
					->orWhere('domain', 'like', '%' . $request->input('q') . '%');
				if(!$request->has('dups')) {
					$q = $q->groupBy('shortcode');
				}
				return $q;
			}
		})
		->simplePaginate(10)
		->withQueryString();

		$stats = Cache::remember('pf:admin:custom_emoji:stats', 43200, function() use($pg) {
			$res = [
				'total' => CustomEmoji::count(),
				'active' => CustomEmoji::whereDisabled(false)->count(),
				'remote' => CustomEmoji::where('domain', '!=', config('pixelfed.domain.app'))->count(),
			];

			if($pg) {
				$res['duplicate'] = CustomEmoji::select('shortcode')->groupBy('shortcode')->havingRaw('count(*) > 1')->count();
			} else {
				$res['duplicate'] = CustomEmoji::groupBy('shortcode')->havingRaw('count(*) > 1')->count();
			}

			return $res;
		});

		return view('admin.custom-emoji.home', compact('emojis', 'sort', 'stats'));
	}

	public function customEmojiToggleActive(Request $request, $id)
	{
		abort_unless(config('federation.custom_emoji.enabled'), 404);
		$emoji = CustomEmoji::findOrFail($id);
		$emoji->disabled = !$emoji->disabled;
		$emoji->save();
		$key = CustomEmoji::CACHE_KEY . str_replace(':', '', $emoji->shortcode);
		Cache::forget($key);
		return redirect()->back();
	}

	public function customEmojiAdd(Request $request)
	{
		abort_unless(config('federation.custom_emoji.enabled'), 404);
		return view('admin.custom-emoji.add');
	}

	public function customEmojiStore(Request $request)
	{
		abort_unless(config('federation.custom_emoji.enabled'), 404);
		$this->validate($request, [
			'shortcode' => [
				'required',
				'min:3',
				'max:80',
				'starts_with::',
				'ends_with::',
				Rule::unique('custom_emoji')->where(function ($query) use($request) {
					return $query->whereDomain(config('pixelfed.domain.app'))
					->whereShortcode($request->input('shortcode'));
				})
			],
			'emoji' => 'required|file|mimetypes:jpg,png|max:' . (config('federation.custom_emoji.max_size') / 1000)
		]);

		$emoji = new CustomEmoji;
		$emoji->shortcode = $request->input('shortcode');
		$emoji->domain = config('pixelfed.domain.app');
		$emoji->save();

		$fileName = $emoji->id . '.' . $request->emoji->extension();
		$request->emoji->storeAs('public/emoji', $fileName);
		$emoji->media_path = 'emoji/' . $fileName;
		$emoji->save();
		Cache::forget('pf:custom_emoji');
		return redirect(route('admin.custom-emoji'));
	}

	public function customEmojiDelete(Request $request, $id)
	{
		abort_unless(config('federation.custom_emoji.enabled'), 404);
		$emoji = CustomEmoji::findOrFail($id);
		Storage::delete("public/{$emoji->media_path}");
		Cache::forget('pf:custom_emoji');
		$emoji->delete();
		return redirect(route('admin.custom-emoji'));
	}

	public function customEmojiShowDuplicates(Request $request, $id)
	{
		abort_unless(config('federation.custom_emoji.enabled'), 404);
		$emoji = CustomEmoji::orderBy('id')->whereDisabled(false)->whereShortcode($id)->firstOrFail();
		$emojis = CustomEmoji::whereShortcode($id)->where('id', '!=', $emoji->id)->cursorPaginate(10);
		return view('admin.custom-emoji.duplicates', compact('emoji', 'emojis'));
	}
}
