<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers\Admin;

use Cache;
use App\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
                break;

            case 'unlist':
                $item->visibility = 'unlisted';
                $item->save();
                Cache::forget('profiles:private');
                break;

            case 'delete':
                // Todo: fire delete job
                $report->admin_seen = null;
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
}
