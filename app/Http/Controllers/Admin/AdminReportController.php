<?php

namespace App\Http\Controllers\Admin;

use Cache;
use App\Report;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\AccountService;
use App\Services\StatusService;

trait AdminReportController
{
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
