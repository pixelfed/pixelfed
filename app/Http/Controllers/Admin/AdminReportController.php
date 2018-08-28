<?php

namespace App\Http\Controllers\Admin;

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
                $item->is_nsfw = true;
                $item->save();
                $report->nsfw = true;
                break;

            case 'unlist':
                $item->visibility = 'unlisted';
                $item->save();
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
}
