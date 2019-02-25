<?php

namespace App\Http\Controllers;

use App\{
  FailedJob,
  Hashtag,
  Instance,
  Media,
  Like,
  OauthClient,
  Profile,
  Report,
  Status,
  User
};
use DB, Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Jackiedo\DotenvEditor\DotenvEditor;
use App\Http\Controllers\Admin\{
  AdminDiscoverController,
  AdminInstanceController,
  AdminReportController,
  AdminMediaController,
  AdminSettingsController
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
        $data = Cache::remember('admin:dashboard:home:data', now()->addMinutes(15), function() {
          $day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
          return [
            'failedjobs' => [
              'count' => PrettyNumber::convert(FailedJob::where('failed_at', '>=', \Carbon\Carbon::now()->subDay())->count()),
              'graph' => FailedJob::selectRaw('count(*) as count, '.$day.'failed_at) as d')->groupBy('d')->whereBetween('failed_at',[now()->subDays(24), now()])->orderBy('d')->pluck('count')
            ],
            'reports' => [
              'count' => PrettyNumber::convert(Report::whereNull('admin_seen')->count()),
              'graph' => Report::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at',[now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
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
        return view('admin.home', compact('data'));
    }

    public function users(Request $request)
    {
        $col = $request->query('col') ?? 'id';
        $dir = $request->query('dir') ?? 'desc';
        $stats = $this->collectUserStats($request);
        $users = User::withCount('statuses')->orderBy($col, $dir)->paginate(10);

        return view('admin.users.home', compact('users', 'stats'));
    }

    public function editUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $profile = $user->profile;
        return view('admin.users.edit', compact('user', 'profile'));
    }

    public function statuses(Request $request)
    {
        $statuses = Status::orderBy('id', 'desc')->paginate(10);

        return view('admin.statuses.home', compact('statuses'));
    }

    public function showStatus(Request $request, $id)
    {
        $status = Status::findOrFail($id);

        return view('admin.statuses.show', compact('status'));
    }

    public function reports(Request $request)
    {
      $filter = $request->input('filter');
      if(in_array($filter, ['open', 'closed'])) {
        if($filter == 'open') {
          $reports = Report::orderBy('created_at','desc')
            ->whereNotNull('admin_seen')
            ->paginate(10);
        } else {
          $reports = Report::orderBy('created_at','desc')
            ->whereNull('admin_seen')
            ->paginate(10);        
        }
      } else {
        $reports = Report::orderBy('created_at','desc')
          ->paginate(10);
      }
      return view('admin.reports.home', compact('reports'));
    }

    public function showReport(Request $request, $id)
    {
      $report = Report::findOrFail($id);
      return view('admin.reports.show', compact('report'));
    }

    protected function collectUserStats($request)
    { 
      $total_duration = $request->query('total_duration') ?? '30';
      $new_duration = $request->query('new_duration') ?? '7';
      $stats = [];
      $stats['total'] = [
        'count' => User::where('created_at', '>', Carbon::now()->subDays($total_duration))->count(),
        'points' => 0//User::selectRaw(''.$day.'created_at) day, count(*) as count')->where('created_at','>', Carbon::now()->subDays($total_duration))->groupBy('day')->pluck('count')
      ];
      $stats['new'] = [
        'count' => User::where('created_at', '>', Carbon::now()->subDays($new_duration))->count(),
        'points' => 0//User::selectRaw(''.$day.'created_at) day, count(*) as count')->where('created_at','>', Carbon::now()->subDays($new_duration))->groupBy('day')->pluck('count')
      ];
      $stats['active'] = [
        'count' => Status::groupBy('profile_id')->count()
      ];
      $stats['profile'] = [
        'local' => Profile::whereNull('remote_url')->count(),
        'remote' => Profile::whereNotNull('remote_url')->count()
      ];
      $stats['avg'] = [
        'likes' => floor(Like::average('profile_id')),
        'posts' => floor(Status::avg('profile_id'))
      ];
      return $stats;

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
        $profiles = Profile::select('id','username')->where('username','like', "%$search%")->orderBy('id','desc')->paginate($limit);
      } else if($filter && $order) {
        $profiles = Profile::select('id','username')->withCount(['likes','statuses','followers'])->orderBy($filter, $order)->paginate($limit);
      } else {
        $profiles = Profile::select('id','username')->orderBy('id','desc')->paginate($limit);
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

}
