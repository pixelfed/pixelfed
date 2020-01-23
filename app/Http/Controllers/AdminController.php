<?php

namespace App\Http\Controllers;

use App\{
  Contact,
  FailedJob,
  Hashtag,
  Instance,
  Media,
  Like,
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
  AdminSupportController
};
use App\Util\Lexer\PrettyNumber;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use AdminReportController, 
        AdminDiscoverController, 
        AdminMediaController, 
        AdminSettingsController, 
        AdminInstanceController;

    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('twofactor');
    }

    public function home()
    {
        $day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';

        $recent = Cache::remember('admin:dashboard:home:data:15min', now()->addMinutes(15), function() use ($day) {
          return [
            'contact' => [
              'count' => PrettyNumber::convert(Contact::whereNull('read_at')->count()),
              'graph' => Contact::selectRaw('count(*) as count, '.$day.'created_at) as d')->groupBy('d')->whereNull('read_at')->whereBetween('created_at',[now()->subDays(14), now()])->orderBy('d')->pluck('count')
            ],
            'failedjobs' => [
              'count' => PrettyNumber::convert(FailedJob::where('failed_at', '>=', \Carbon\Carbon::now()->subDay())->count()),
              'graph' => FailedJob::selectRaw('count(*) as count, '.$day.'failed_at) as d')->groupBy('d')->whereBetween('failed_at',[now()->subDays(14), now()])->orderBy('d')->pluck('count')
            ],
            'reports' => [
              'count' => PrettyNumber::convert(Report::whereNull('admin_seen')->count()),
              'graph' => Report::selectRaw('count(*) as count, '.$day.'created_at) as d')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('d')->orderBy('d')->pluck('count')
            ],
            'statuses' => [
              'count' => PrettyNumber::convert(Status::whereNull('in_reply_to_id')->whereNull('reblog_of_id')->count()),
              'graph' => Status::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'replies' => [
              'count' => PrettyNumber::convert(Status::whereNotNull('in_reply_to_id')->count()),
              'graph' => Status::whereNotNull('in_reply_to_id')->selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'shares' => [
              'count' => PrettyNumber::convert(Status::whereNotNull('reblog_of_id')->count()),
              'graph' => Status::whereNotNull('reblog_of_id')->selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'likes' => [
              'count' => PrettyNumber::convert(Like::count()),
              'graph' => Like::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'profiles' => [
              'count' => PrettyNumber::convert(Profile::count()),
              'graph' => Profile::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
          ];
        });

        $longer = Cache::remember('admin:dashboard:home:data:24hr', now()->addHours(24), function() use ($day) {
          return [
            'users' => [
              'count' => PrettyNumber::convert(User::count()),
              'graph' => User::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'instances' => [
              'count' => PrettyNumber::convert(Instance::count()),
              'graph' => Instance::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(28), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'media' => [
              'count' => PrettyNumber::convert(Media::count()),
              'graph' => Media::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ],
            'storage' => [
              'count' => Media::sum('size'),
              'graph' => Media::selectRaw('sum(size) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
            ]
          ];
        });

        $data = array_merge($recent, $longer);
        return view('admin.home', compact('data'));
    }

    public function users(Request $request)
    {
        $col = $request->query('col') ?? 'id';
        $dir = $request->query('dir') ?? 'desc';
        $users = User::select('id', 'username', 'status')->withCount('statuses')->orderBy($col, $dir)->simplePaginate(10);

        return view('admin.users.home', compact('users'));
    }

    public function editUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $profile = $user->profile;
        return view('admin.users.edit', compact('user', 'profile'));
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

    public function profiles(Request $request)
    {
      $this->validate($request, [
        'search' => 'nullable|string|max:250',
        'filter' => [
          'nullable',
          'string',
          Rule::in(['id','username','statuses_count','followers_count','likes_count'])
        ],
        'order' => [
          'nullable',
          'string',
          Rule::in(['asc','desc'])
        ],
        'layout' => [
          'nullable',
          'string',
          Rule::in(['card','list'])
        ],
        'limit' => 'nullable|integer|min:1|max:50'
      ]);
      $search = $request->input('search');
      $filter = $request->input('filter');
      $order = $request->input('order') ?? 'desc';
      $limit = $request->input('limit') ?? 12;
      if($search) {
        $profiles = Profile::select('id','username')->where('username','like', "%$search%")->orderBy('id','desc')->simplePaginate($limit);
      } else if($filter && $order) {
        $profiles = Profile::select('id','username')->withCount(['likes','statuses','followers'])->orderBy($filter, $order)->simplePaginate($limit);
      } else {
        $profiles = Profile::select('id','username')->orderBy('id','desc')->simplePaginate($limit);
      }

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
