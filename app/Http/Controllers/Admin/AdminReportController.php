<?php

namespace App\Http\Controllers\Admin;

use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\AccountService;
use App\Services\StatusService;
use App\{
	AccountInterstitial,
	Contact,
	Hashtag,
	Newsroom,
	OauthClient,
	Profile,
	Report,
	Status,
	Story,
	User
};
use Illuminate\Validation\Rule;
use App\Services\StoryService;

trait AdminReportController
{
	public function reports(Request $request)
	{
		$filter = $request->input('filter') == 'closed' ? 'closed' : 'open';
		$page = $request->input('page') ?? 1;

		$ai = Cache::remember('admin-dash:reports:ai-count', 3600, function() {
			return AccountInterstitial::whereNotNull('appeal_requested_at')->whereNull('appeal_handled_at')->count();
		});

		$spam = Cache::remember('admin-dash:reports:spam-count', 3600, function() {
			return AccountInterstitial::whereType('post.autospam')->whereNull('appeal_handled_at')->count();
		});

		$mailVerifications = Redis::scard('email:manual');

		if($filter == 'open' && $page == 1) {
			$reports = Cache::remember('admin-dash:reports:list-cache', 300, function() use($page, $filter) {
				return Report::whereHas('status')
					->whereHas('reportedUser')
					->whereHas('reporter')
					->orderBy('created_at','desc')
					->when($filter, function($q, $filter) {
						return $filter == 'open' ?
						$q->whereNull('admin_seen') :
						$q->whereNotNull('admin_seen');
					})
					->paginate(6);
			});
		} else {
			$reports = Report::whereHas('status')
			->whereHas('reportedUser')
			->whereHas('reporter')
			->orderBy('created_at','desc')
			->when($filter, function($q, $filter) {
				return $filter == 'open' ?
				$q->whereNull('admin_seen') :
				$q->whereNotNull('admin_seen');
			})
			->paginate(6);
		}

		return view('admin.reports.home', compact('reports', 'ai', 'spam', 'mailVerifications'));
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

			Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $appeal->user->profile_id);
			Cache::forget('pf:bouncer_v0:recent_by_pid:' . $appeal->user->profile_id);
			Cache::forget('admin-dash:reports:spam-count');
			return redirect('/i/admin/reports/autospam');
		}

		$status = $appeal->status;
		$status->is_nsfw = $meta->is_nsfw;
		$status->scope = 'public';
		$status->visibility = 'public';
		$status->save();

		$appeal->appeal_handled_at = now();
		$appeal->save();

		StatusService::del($status->id);

		Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $appeal->user->profile_id);
		Cache::forget('pf:bouncer_v0:recent_by_pid:' . $appeal->user->profile_id);
		Cache::forget('admin-dash:reports:spam-count');

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
			Cache::forget('admin-dash:reports:ai-count');
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
		StatusService::del($status->id);
		Cache::forget('admin-dash:reports:ai-count');

		return redirect('/i/admin/reports/appeals');
	}

    public function updateReport(Request $request, $id)
    {
        $this->validate($request, [
            'action'	=> 'required|string',
        ]);

        $action = $request->input('action');

        $actions = [
            'ignore',
            'cw',
            'unlist',
            'delete',
            'shadowban',
            'ban',
        ];

        if (!in_array($action, $actions)) {
            return abort(403);
        }

        $report = Report::findOrFail($id);

        $this->handleReportAction($report, $action);
        Cache::forget('admin-dash:reports:list-cache');

        return response()->json(['msg'=> 'Success']);
    }

    public function handleReportAction(Report $report, $action)
    {
        $item = $report->reported();
        $report->admin_seen = Carbon::now();

        switch ($action) {
            case 'ignore':
                $report->not_interested = true;
                break;

            case 'cw':
                Cache::forget('status:thumb:'.$item->id);
                $item->is_nsfw = true;
                $item->save();
                $report->nsfw = true;
                StatusService::del($item->id);
                break;

            case 'unlist':
                $item->visibility = 'unlisted';
                $item->save();
                Cache::forget('profiles:private');
                StatusService::del($item->id);
                break;

            case 'delete':
                // Todo: fire delete job
                $report->admin_seen = null;
                StatusService::del($item->id);
                break;

            case 'shadowban':
                // Todo: fire delete job
                $report->admin_seen = null;
                break;

            case 'ban':
                // Todo: fire delete job
                $report->admin_seen = null;
                break;

            default:
                $report->admin_seen = null;
                break;
        }

        $report->save();

        return $this;
    }

    protected function actionMap()
    {
        return [
            '1' => 'ignore',
            '2' => 'cw',
            '3' => 'unlist',
            '4' => 'delete',
            '5' => 'shadowban',
            '6' => 'ban'
        ];
    }

    public function bulkUpdateReport(Request $request)
    {
        $this->validate($request, [
            'action' => 'required|integer|min:1|max:10',
            'ids'    => 'required|array'
        ]);
        $action = $this->actionMap()[$request->input('action')];
        $ids = $request->input('ids');
        $reports = Report::whereIn('id', $ids)->whereNull('admin_seen')->get();
        foreach($reports as $report) {
            $this->handleReportAction($report, $action);
        }
        $res = [
            'message' => 'Success',
            'code'    => 200
        ];
        return response()->json($res);
    }

    public function reportMailVerifications(Request $request)
    {
    	$ids = Redis::smembers('email:manual');
    	$ignored = Redis::smembers('email:manual-ignored');
    	$reports = [];
    	if($ids) {
			$reports = collect($ids)
				->filter(function($id) use($ignored) {
					return !in_array($id, $ignored);
				})
				->map(function($id) {
					$account = AccountService::get($id);
					$user = User::whereProfileId($id)->first();
					if(!$user) {
						return [];
					}
					$account['email'] = $user->email;
					return $account;
				})
				->filter(function($res) {
					return isset($res['id']);
				})
				->values();
    	}
    	return view('admin.reports.mail_verification', compact('reports', 'ignored'));
    }

    public function reportMailVerifyIgnore(Request $request)
    {
    	$id = $request->input('id');
    	Redis::sadd('email:manual-ignored', $id);
    	return redirect('/i/admin/reports');
    }

    public function reportMailVerifyApprove(Request $request)
    {
    	$id = $request->input('id');
    	$user = User::whereProfileId($id)->firstOrFail();
    	Redis::srem('email:manual', $id);
    	Redis::srem('email:manual-ignored', $id);
    	$user->email_verified_at = now();
    	$user->save();
    	return redirect('/i/admin/reports');
    }

    public function reportMailVerifyClearIgnored(Request $request)
    {
    	Redis::del('email:manual-ignored');
    	return [200];
    }
}
