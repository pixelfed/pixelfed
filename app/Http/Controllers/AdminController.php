<?php

namespace App\Http\Controllers;

use App\Media;
use App\Like;
use App\Profile;
use App\Report;
use App\Status;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Jackiedo\DotenvEditor\DotenvEditor;
use App\Http\Controllers\Admin\AdminReportController;
use App\Util\Lexer\PrettyNumber;

class AdminController extends Controller
{
    use AdminReportController;

    public function __construct()
    {
        $this->middleware('admin');
        $this->middleware('twofactor');
    }

    public function home()
    {
        return view('admin.home');
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

    public function media(Request $request)
    {
        $media = Status::whereHas('media')->orderby('id', 'desc')->paginate(12);

        return view('admin.media.home', compact('media'));
    }

    public function reports(Request $request)
    {
      $reports = Report::orderBy('created_at','desc')->paginate(12);
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
        'points' => 0//User::selectRaw('day(created_at) day, count(*) as count')->where('created_at','>', Carbon::now()->subDays($total_duration))->groupBy('day')->pluck('count')
      ];
      $stats['new'] = [
        'count' => User::where('created_at', '>', Carbon::now()->subDays($new_duration))->count(),
        'points' => 0//User::selectRaw('day(created_at) day, count(*) as count')->where('created_at','>', Carbon::now()->subDays($new_duration))->groupBy('day')->pluck('count')
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
}
